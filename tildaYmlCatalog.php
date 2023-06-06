<?
require __DIR__ . '/inc/headers-cors.php';
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/functions-apiRetailCrm.php';

/**
 * синхронизируем каталог товаров Тильды и RetailCrm через yml
 * cron: каждые 5 минут
 */

use LireinCore\YMLParser\YML;

$log = ['errors' => []];
$urls = [
	'2steblya' => 'https://2steblya.ru/tstore/yml/81f7b18311537d636b0044cd46380d01.yml'
	//'2steblya' => 'https://tilda.imb-service.ru/file/get/e51ac7a9e59a5d2b84a945de066810c7.yml'
	//'Stay True flowers' => 'https://staytrueflowers.ru/tstore/yml/738043006302c6389361eac93fc53c27.yml'
	//'Stay True flowers' => 'https://tilda.imb-service.ru/file/get/8aec43b5129e6d08c5245877df744cec.yml'
];
foreach ($urls as $site => $url) {
	if (!catalogIsChanged($site, $url)) continue;
	$offersIds = [];
	$catalog = ymlToArray($url);
	$log['catalogInitial'] = $catalog;
	$catalog = optimizeOffers($catalog);
	$catalog = preserveDisabledOffers($catalog);
	$log['catalogOptimized'] = $catalog;
	arrayToYml($catalog);
}
die(json_encode($log));

function arrayToYml($catalog)
{
	/**
	 * собираем и сохраянем yml файл
	 * сохраняем в текстовый файл json, чтобы использовать его в preserveDisabledOffers
	 */
	$out = [];
	$out[] = '<?xml version="1.0" encoding="UTF-8"?>';
	$out[] = '<yml_catalog date="' . $catalog['date'] . '">';
	$out[] = '<shop>';
	$out[] = '<name>' . $catalog['shopData']['name'] . '</name>';
	$out[] = '<company>' . $catalog['shopData']['company'] . '</company>';
	$out[] = '<platform>' . $catalog['shopData']['platform'] . '</platform>';
	$out[] = '<version>' . $catalog['shopData']['version'] . '</version>';
	$out[] = '<currencies>';
	foreach ($catalog['shopData']['currencies'] as $currency) {
		$out[] = '<currency id="' . $currency['id'] . '" rate="' . $currency['rate'] . '"/>';
	}
	$out[] = '</currencies>';
	//$out[] = '<categories></categories>';
	$out[] = '<offers>';
	foreach ($catalog['offers'] as $offer) {
		preg_match('/\-(\d+)\-/', $offer['url'], $id);
		$out[] = '<offer id="' . $offer['id'] . '" productId="' . $id[1] . '" quantity="9999">'; //quantity не работает, так как в срм ручное управление остатками. меняем через api
		$out[] = '<name>' . $offer['name'] . '</name>';
		$out[] = '<vendorCode>' . $offer['vendorCode'] . '</vendorCode>';
		//$out[] = '<description></description>';
		$out[] = '<picture>' . $offer['pictures'][0] . '</picture>';
		$out[] = '<url>' . $offer['url'] . '</url>';
		$out[] = '<price>' . $offer['price'] . '</price>';
		$out[] = '<currencyId>' . $offer['currencyId'] . '</currencyId>';
		//$out[] = '<categoryId>' . $offer['categoryId'] . '</categoryId>';
		/*foreach ($offer['params'] as $name => $value) {
			$out[] = '<param name="' . $name . '">' . $value . '</param>';
		}*/
		$out[] = '</offer>';
	}
	$out[] = '</offers>';
	$out[] = '</shop>';
	$out[] = '</yml_catalog>';
	writeFile(__DIR__ . '/tildaYmlCatalog-' . $catalog['shopData']['name'] . '.yml', implode("\r\n", $out));
	writeFile(__DIR__ . '/tildaYmlCatalog-' . $catalog['shopData']['name'] . '.txt', json_encode($catalog));
}
function preserveDisabledOffers($catalogNew)
{
	/**
	 * получаем каталог прошлой генерации
	 * находим в тем товары, которых нет в новом каталоге
	 * добавляем эти товары в новый каталог (таким образом не теряем ни один товар, который когда-либо был опубликован на сайте)
	 * обновляем файл
	 */
	global $log, $offersIds;
	$catalogOld = json_decode(file_get_contents(__DIR__ . '/tildaYmlCatalog-' . $catalogNew['shopData']['name'] . '.txt'), true);
	$offersToPreserve = [];
	foreach ($catalogOld['offers'] as $offer) {
		if (in_array($offer['id'], $offersIds)) continue;
		$catalogNew['offers'][] = $offer;
		$offersToPreserve[] = $offer;
	}
	$log['offersPreserved'] = $offersToPreserve;
	$catalogNew['offersCount'] = count($catalogNew['offers']);
	return $catalogNew;
}
function optimizeOffers($catalog)
{
	/**
	 * удаляем из каталога неоригинальные витринные букеты
	 * удаляем из каталога category и params
	 * собираем массив ключей по id
	 */
	global $log, $offersIds;
	$offersIdsLocal = [];
	$offersToRemove = [];
	for ($key = 0; $key < $catalog['offersCount']; $key++) {
		if (preg_match('/(\d+)v1$/', $catalog['offers'][$key]['id'], $offerIdTrimmed)) { //если это первый товар из списка товаров с дополнительной наценкой
			$catalog['offers'][$key]['name'] = preg_replace('/\s\-\s.*$/', '', $catalog['offers'][$key]['name']); //отрезаем суффикс из имени товара
			$catalog['offers'][$key]['id'] = $offerIdTrimmed[1]; // меняем id на нормальный (без "v1")
		}
		$conditions = [
			substr($catalog['offers'][$key]['vendorCode'], -1) == 'v', //если это витринный вариант каталожного товара: на конце vendorCode литера "v" (001-5v)
			preg_match('/v\d+$/', $catalog['offers'][$key]['id']) //если это товар, с дополнительной наценкой: на конце id "v\d" (999999v2)
		];
		foreach ($conditions as $condition) {
			if (!$condition) continue;
			$offersToRemove[] = [$key, $catalog['offers'][$key]['name']];
			break;
		}
		if (!in_array($catalog['offers'][$key]['id'], $offersIdsLocal)) { // проверяем на дубликаты (встречал такое)
			$offersIdsLocal[] = $catalog['offers'][$key]['id'];
		} else {
			$offersToRemove[] = [$key, $catalog['offers'][$key]['name']];
		}
		if (preg_match('/^LOVE IS\.\.\./', $catalog['offers'][$key]['name'])) {
			$catalog['offers'][$key]['name'] = 'LOVE IS' . $catalog['offers'][$key]['description'];
		}
		if (preg_match('/^777\-/', $catalog['offers'][$key]['vendorCode'])) {
			$catalog['offers'][$key]['name'] = preg_replace('/\s\-\s.*$/', '', $catalog['offers'][$key]['name']); //отрезаем суффикс из имени товара
		}
	}
	foreach ($offersToRemove as $offerRemoved) {
		$log['offersRemoved'][] = $offerRemoved[1];
		unset($catalog['offers'][$offerRemoved[0]]);
	}
	$catalog['offers'] = array_values($catalog['offers']);
	$catalog['offersCount'] = count($catalog['offers']);
	foreach ($catalog['offers'] as $offer) {
		$offersIds[] = $offer['id']; //собираем массив айдишников (используется в preserveDisabledProducts)
	}
	return $catalog;
}
function ymlToArray($url)
{
	/**
	 * конвертируем yml файл в массив
	 */
	global $log;
	$catalog = [];
	$ymlParser = new YML();
	try {
		$ymlParser->parse($url);
		$catalog['date'] = $ymlParser->getDate();
		$shop = $ymlParser->getShop();
		if ($shop->isValid()) {
			$catalog['offersCount'] = $shop->getOffersCount();
			$catalog['shopData'] = $shop->getData();
			$catalog['offers'] = [];
			foreach ($ymlParser->getOffers() as $offer) {
				if ($offer->isValid()) {
					$catalog['offers'][] = $offer->getData();
				} else {
					$log['errors'][] = $offer->getErrors();
				}
			}
		} else {
			$log['errors'][] = $shop->getErrors();
		}
	} catch (\Exception $e) {
		$log['errors'][] = $e->getMessage();
	}
	return $catalog;
}
function catalogIsChanged($site, $url)
{
	/**
	 * сверяем хэш содержимого каталога из тильды и хэш, сохраненный в локальном файле
	 * если хэши одинаковые, значит каталог не был изменен
	 * если отличаются, перезаписываем новый хэш
	 */
	global $log;
	$newHash = hash('md5', file_get_contents($url));
	$hashFileUrl = __DIR__ . '/tildaYmlCatalogHash-' . $site . '.txt';
	$oldHash = file_get_contents($hashFileUrl);
	$log['hash'] = [];
	$log['hash']['new'] = $newHash;
	$log['hash']['old'] = $oldHash;
	$isChanged = ($newHash != $oldHash);
	if (!$isChanged) {
		$log['errors'][] = 'каталог не изменился';
	} else {
		writeFile($hashFileUrl, $newHash);
	}
	return $isChanged;
}
