<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\YML;

class TildaTovarsToDB
{
	private $db;
	private $site;

	public function __construct($scriptData = [])
	{
		$this->db = new DB();
		$this->site = isset($scriptData['site']) ? $scriptData['site'] : null;
	}

	public function init(): void
	{
		$urls = [
			'2steblya' => $_ENV['yml_catalog_2steblya'],
			'staytrueflowers' => $_ENV['yml_catalog_stf']
		];
		foreach ($urls as $site => $url) {
			if ($this->site && $site != $this->site) continue;

			//парсим каталог тильды и добавляем в массив айдишники с названиями товаров
			$catalog = YML::ymlToArray($url);
			$tovars = [];
			foreach ($catalog['offers'] as $offer) {
				$tovars[$offer[isset($offer['groupId']) ? 'groupId' : 'id']] = $this->trimTitle($offer['name']);
			}

			//прогоняем массив, чтоб получить новый массив, сформированный для отправки в DB
			$tovarsToDb = [];
			$shopId = $this->getShopId($site);
			foreach ($tovars as $id => $title) {
				$tovarsToDb[] = [
					'id' => $id,
					'title' => $title,
					'shop_crm_id' => $shopId
				];
			}
			//$tovarsToDb = array_slice($tovarsToDb, 0, 3);

			//пишем в DB
			$this->db->sql("INSERT IGNORE INTO tovars (id,title,shop_crm_id) VALUES (:id,:title,:shop_crm_id)", $tovarsToDb);

			//тут надо написать проверку на удаленные товары из тильды и реализовать их удаление из базы
		}
	}

	// обрезаем "букетусик", "букетик" и пр.
	private function trimTitle($title)
	{
		$lastHyphenPosition = strrpos($title, ' - ');
		if ($lastHyphenPosition !== false) $title = substr($title, 0, $lastHyphenPosition);
		return $title;
	}

	//получаем id магазина
	private function getShopId($site)
	{
		$response = $this->db->sql("SELECT shop_crm_id FROM shops WHERE crm_shop_code = '$site'");
		if (!$response) return null;
		return intval($response[0]->shop_crm_id);
	}
}
