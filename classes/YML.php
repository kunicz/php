<?

namespace php2steblya;

use php2steblya\Logger;
use LireinCore\YMLParser\YML as YMLparser;
use php2steblya\LoggerException as Exception;

class YML
{
	public static function ymlToArray(string $url)
	{
		/**
		 * конвертируем yml файл в массив
		 */
		$log = new Logger('YMLparser');
		$catalog = [];
		$ymlParser = new YMLparser();
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
						$log->pushError($offer->getErrors());
					}
				}
			} else {
				$log->pushError($shop->getErrors());
			}
		} catch (Exception $e) {
			$e->abort($log);
		}
		return $catalog;
	}
	public static function arrayToYml(array $catalog)
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
		return implode("\r\n", $out);
	}
}
