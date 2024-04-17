<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\YML;
use php2steblya\File;
use php2steblya\Logger;
use php2steblya\Finish;

class Tilda_yml_catalog extends Script
{
	private $urlTilda;
	private $urlLocal;
	private $catalog;

	public function __construct($scriptData = [])
	{
		$this->db = DB::getInstance();
		$this->logger = Logger::getInstance();
		$this->logger->addToLog('script', __CLASS__);
	}

	public function init()
	{
		$sitesFromDB = $this->getSitesFromDB();
		if (empty($sitesFromDB)) return;
		foreach ($sitesFromDB as $siteFromDB) {
			$this->site = $this->getSite($siteFromDB);
			$this->urlTilda = $this->getUrlTilda();
			$this->urlLocal = $this->getUrlLocal();

			if (!$this->urlTilda || !$this->urlLocal) continue;
			if (!$this->isChanged()) continue;

			$this->catalog = YML::ymlToArray($this->urlTilda);
			$this->logger->addToLog($this->site->shop_crm_code . '_catalog_1_new', $this->catalog);

			$this->preserveDisabledOffers();
			$this->optimizeOffers();
			$this->updateCatalog();
		}
		Finish::success();
	}

	private function isChanged()
	{
		return $this->getOldHash() !== $this->getNewHash();
	}

	private function preserveDisabledOffers()
	{
		/**
		 * получаем каталог прошлой генерации
		 * находим в нем товары, которых нет в новом каталоге
		 * добавляем эти товары в новый каталог (таким образом не теряем ни один товар, который когда-либо был опубликован на сайте)
		 */
		$response = $this->db->sql("SELECT catalog FROM tilda_yml_catalog WHERE shop_crm_id = '{$this->site->shop_crm_id}'");
		$catalogOld = isset($response[0]->catalog) ? json_decode($response[0]->catalog, true) : [];
		$this->logger->addToLog($this->site->shop_crm_code . '_catalog_2_old', $catalogOld);
		$offersIds = $this->getOffersIds();
		$offersToPreserve = [];
		foreach ($catalogOld['offers'] as $offer) {
			if (in_array($offer['id'], $offersIds)) continue;
			$this->catalog['offers'][] = $offer;
			$offersToPreserve[] = $offer['name'];
		}
		$this->catalog['offersCount'] = count($this->catalog['offers']);
		$this->logger->addToLog($this->site->shop_crm_code . '_offers_to_preserve', $offersToPreserve);
		$this->logger->addToLog($this->site->shop_crm_code . '_catalog_3_with_preserved', $this->catalog);
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
		for ($i = 0; $i < $this->catalog['offersCount']; $i++) {
			if (preg_match('/(\d+)v1$/', $this->catalog['offers'][$i]['id'], $offerIdTrimmed)) { //если это первый товар из списка товаров с дополнительной наценкой
				$this->catalog['offers'][$i]['name'] = preg_replace('/\s\-\s.*$/', '', $this->catalog['offers'][$i]['name']); //отрезаем суффикс из имени товара
				$this->catalog['offers'][$i]['id'] = $offerIdTrimmed[1]; // меняем id на нормальный (без "v1")
			}
			$conditions = [
				substr($this->catalog['offers'][$i]['vendorCode'], -1) == 'v', //если это витринный вариант каталожного товара: на конце vendorCode литера "v" (001-5v)
				preg_match('/v\d+$/', $this->catalog['offers'][$i]['id']) //если это товар, с дополнительной наценкой: на конце id "v\d" (999999v2)
			];
			foreach ($conditions as $condition) {
				if (!$condition) continue;
				$offersToRemove[] = [$i, $this->catalog['offers'][$i]['name']];
				break;
			}
			if (!in_array($this->catalog['offers'][$i]['id'], $offersIdsLocal)) { // проверяем на дубликаты (встречал такое)
				$offersIdsLocal[] = $this->catalog['offers'][$i]['id'];
			} else {
				$offersToRemove[] = [$i, $this->catalog['offers'][$i]['name']];
			}
			if (preg_match('/^LOVE IS\.\.\./', $this->catalog['offers'][$i]['name'])) {
				$this->catalog['offers'][$i]['name'] = 'LOVE IS' . $this->catalog['offers'][$i]['description'];
			}
			if (preg_match('/^777\-/', $this->catalog['offers'][$i]['vendorCode'])) {
				$this->catalog['offers'][$i]['name'] = preg_replace('/\s\-\s.*$/', '', $this->catalog['offers'][$i]['name']); //отрезаем суффикс из имени товара
			}
		}
		$offersRemovedNames = [];
		foreach ($offersToRemove as $offerRemoved) {
			$offersRemovedNames[] = $offerRemoved[1];
			unset($this->catalog['offers'][$offerRemoved[0]]);
		}
		$this->catalog['offers'] = array_values($this->catalog['offers']);
		$this->catalog['offersCount'] = count($this->catalog['offers']);

		$this->logger->addToLog($this->site->shop_crm_code . '_catalog_4_optimized', $this->catalog);
		$this->logger->addToLog($this->site->shop_crm_code . '_offers_to_remove', $offersRemovedNames);
	}

	private function updateCatalog()
	{
		$yml = YML::arrayToYml($this->catalog);
		$file = new File($this->urlLocal);
		$file->write($yml);
		$params = [
			'id' => $this->site->shop_crm_id,
			'hash' => $this->getNewHash(),
			'catalog' => json_encode($this->catalog)
		];
		$this->db->sql("UPDATE tilda_yml_catalog SET catalog = :catalog, hash = :hash WHERE shop_crm_id = :id", $params);
	}

	private function getSite($site)
	{
		$this->logger->addToLog($site->shop_crm_code . '_site', $site);
		return $site;
	}
	private function getUrlTilda()
	{
		$response = $this->db->sql("SELECT url FROM tilda_yml_catalog WHERE shop_crm_id = '{$this->site->shop_crm_id}'");
		$url = isset($response[0]->url) ? $response[0]->url : null;
		$this->logger->addToLog($this->site->shop_crm_code . '_url_tilda', $url);
		return $url;
	}
	private function getUrlLocal()
	{
		$url = dirname(dirname(dirname(__FILE__))) . '/tilda_catalog_' . $this->site->shop_crm_code . '.yml';
		$this->logger->addToLog($this->site->shop_crm_code . '_url_local', $url);
		return $url;
	}
	private function getOffersIds()
	{
		$offersIds = [];
		foreach ($this->catalog['offers'] as $offer) {
			$offersIds[] = $offer['id'];
		}
		return $offersIds;
	}
	private function getNewHash()
	{
		$hash = hash('md5', file_get_contents($this->urlTilda));
		$this->logger->addToLog($this->site->shop_crm_code . '_hash_new', $hash);
		return $hash;
	}
	private function getOldHash()
	{
		$response = $this->db->sql("SELECT hash	FROM tilda_yml_catalog WHERE shop_crm_id = '{$this->site->shop_crm_id}'");
		$hash = isset($response[0]->hash) ? $response[0]->hash : '';
		$this->logger->addToLog($this->site->shop_crm_code . '_hash_old', $hash);
		return $hash;
	}
}
