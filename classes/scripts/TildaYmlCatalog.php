<?

namespace php2steblya\scripts;

/**
 * синхронизируем каталог товаров Тильды и RetailCrm через yml
 * cron: каждые 5 минут
 */

use php2steblya\YML;
use php2steblya\File;
use php2steblya\Logger;

class TildaYmlCatalog
{
	public $log;
	private $tildaYmlUrl;
	private $site;
	private $catalog;
	private array $offersIds;
	private array $filePaths;

	public function init()
	{
		$this->log = new Logger('tilda yml catalogs sync');
		$urls = [
			[
				'site' => $_ENV['site_2steblya_id'],
				'url' => $_ENV['yml_catalog_2steblya']
			],
			[
				'site' => $_ENV['site_stf_id'],
				'url' => $_ENV['yml_catalog_stf']
			]
		];
		for ($i = 0; $i < count($urls); $i++) {
			$this->site = $urls[$i]['site'];
			$this->tildaYmlUrl = $urls[$i]['url'];
			$this->filePaths = [
				'catalog.yml' => dirname(dirname(dirname(__FILE__))) . '/TildaYmlCatalog_' . $this->site . '.yml',
				'catalog.txt' => dirname(dirname(dirname(__FILE__))) . '/TildaYmlCatalog_' . $this->site . '.txt',
				'catalog_hash.txt' => dirname(dirname(dirname(__FILE__))) . '/TildaYmlCatalog_' . $this->site . '_hash.txt'
			];
			$this->log->insert($this->site);
			if (!$this->isChanged()) continue;
			$this->catalog = YML::ymlToArray($this->tildaYmlUrl);
			$this->log->push('catalogInitial', $this->catalog);
			$this->optimizeOffers();
			$this->preserveDisabledOffers();
			$yml = YML::arrayToYml($this->catalog);
			$fileYml = new File($this->filePaths['catalog.yml']);
			$fileYml->write($yml);
			$fileOld = new File($this->filePaths['catalog.txt']);
			$fileOld->write(json_encode($this->catalog));
			$this->log->setRemark($this->site);
			$this->log->writeSummary();
		}
	}
	private function isChanged()
	{
		$hashFile = new File($this->filePaths['catalog_hash.txt']);
		$oldHash = $hashFile->getContents();
		$newHash = hash('md5', file_get_contents($this->tildaYmlUrl));
		$this->log->push('hash', ['old' => $oldHash, 'new' => $newHash]);
		if ($newHash == $oldHash) {
			return false;
		}
		$hashFile->write($newHash);
		return true;
	}
	private function optimizeOffers()
	{
		/**
		 * удаляем из каталога неоригинальные витринные букеты
		 * удаляем из каталога category и params
		 * собираем массив ключей по id
		 */
		$offersIdsLocal = [];
		$offersToRemove = [];
		for ($key = 0; $key < $this->catalog['offersCount']; $key++) {
			if (preg_match('/(\d+)v1$/', $this->catalog['offers'][$key]['id'], $offerIdTrimmed)) { //если это первый товар из списка товаров с дополнительной наценкой
				$this->catalog['offers'][$key]['name'] = preg_replace('/\s\-\s.*$/', '', $this->catalog['offers'][$key]['name']); //отрезаем суффикс из имени товара
				$this->catalog['offers'][$key]['id'] = $offerIdTrimmed[1]; // меняем id на нормальный (без "v1")
			}
			$conditions = [
				substr($this->catalog['offers'][$key]['vendorCode'], -1) == 'v', //если это витринный вариант каталожного товара: на конце vendorCode литера "v" (001-5v)
				preg_match('/v\d+$/', $this->catalog['offers'][$key]['id']) //если это товар, с дополнительной наценкой: на конце id "v\d" (999999v2)
			];
			foreach ($conditions as $condition) {
				if (!$condition) continue;
				$offersToRemove[] = [$key, $this->catalog['offers'][$key]['name']];
				break;
			}
			if (!in_array($this->catalog['offers'][$key]['id'], $offersIdsLocal)) { // проверяем на дубликаты (встречал такое)
				$offersIdsLocal[] = $this->catalog['offers'][$key]['id'];
			} else {
				$offersToRemove[] = [$key, $this->catalog['offers'][$key]['name']];
			}
			if (preg_match('/^LOVE IS\.\.\./', $this->catalog['offers'][$key]['name'])) {
				$this->catalog['offers'][$key]['name'] = 'LOVE IS' . $this->catalog['offers'][$key]['description'];
			}
			if (preg_match('/^777\-/', $this->catalog['offers'][$key]['vendorCode'])) {
				$this->catalog['offers'][$key]['name'] = preg_replace('/\s\-\s.*$/', '', $this->catalog['offers'][$key]['name']); //отрезаем суффикс из имени товара
			}
		}
		$offersRemovedNames = [];
		foreach ($offersToRemove as $offerRemoved) {
			$offersRemovedNames[] = $offerRemoved[1];
			unset($this->catalog['offers'][$offerRemoved[0]]);
		}
		$this->catalog['offers'] = array_values($this->catalog['offers']);
		$this->catalog['offersCount'] = count($this->catalog['offers']);
		foreach ($this->catalog['offers'] as $offer) {
			$this->offersIds[] = $offer['id']; //собираем массив айдишников (используется в preserveDisabledProducts)
		}
		$this->log->push('offersRemoved', $offersRemovedNames);
	}
	private function preserveDisabledOffers()
	{
		/**
		 * получаем каталог прошлой генерации
		 * находим в тем товары, которых нет в новом каталоге
		 * добавляем эти товары в новый каталог (таким образом не теряем ни один товар, который когда-либо был опубликован на сайте)
		 * обновляем файл
		 */
		$catalogOldFile = new File($this->filePaths['catalog.txt']);
		$catalogOld = json_decode($catalogOldFile->getContents(), true);
		$offersToPreserve = [];
		foreach ($catalogOld['offers'] as $offer) {
			if (in_array($offer['id'], $this->offersIds)) continue;
			$this->catalog['offers'][] = $offer;
			$offersToPreserve[] = $offer;
		}
		$this->catalog['offersCount'] = count($this->catalog['offers']);
		$this->log->push('offersPreserved', $offersToPreserve);
	}
}
