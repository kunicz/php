<?

namespace php2steblya\scripts;

use php2steblya\YML;
use php2steblya\Finish;

class Tilda_products_to_DB extends Script
{
	public function init(): void
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			$sitesFromDB = $this->site ? $this->getSiteFromDB(['code' => $this->site]) : $this->getSitesFromDB();
			foreach ($sitesFromDB as $site) {
				$this->site = $site;

				//парсим каталог тильды и добавляем в массив айдишники с названиями товаров
				$catalog = YML::ymlToArray($this->getUrlTilda());
				if (!$catalog) continue;
				$products = [];
				foreach ($catalog['offers'] as $offer) {
					$products[$offer[isset($offer['groupId']) ? 'groupId' : 'id']] = $this->trimTitle($offer['name']);
				}

				//прогоняем массив, чтоб получить новый массив, сформированный для отправки в DB
				foreach ($products as $id => $title) {
					$params = [
						'id' => $id,
						'title' => $title,
						'shop_crm_id' => $this->site->shop_crm_id
					];
					$this->db->sql("INSERT IGNORE INTO products (id,title,shop_crm_id) VALUES (:id,:title,:shop_crm_id)", $params);
				}

				//тут надо написать проверку на удаленные товары из тильды и реализовать их удаление из базы
			}
			Finish::success();
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	// обрезаем "букетусик", "букетик" и пр.
	private function trimTitle($title)
	{
		$lastHyphenPosition = strrpos($title, ' - ');
		if ($lastHyphenPosition !== false) $title = substr($title, 0, $lastHyphenPosition);
		return $title;
	}

	private function getUrlTilda()
	{
		$response = $this->db->sql("SELECT url FROM tilda_yml_catalog WHERE shop_crm_id = '{$this->site->shop_crm_id}'");
		$url = isset($response[0]->url) ? $response[0]->url : null;
		return $url;
	}
}
