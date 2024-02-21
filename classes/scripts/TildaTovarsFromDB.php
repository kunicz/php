<?

namespace php2steblya\scripts;

use php2steblya\DB;

class TildaTovarsFromDB
{
	private $db;
	private $db_request;
	private $site;

	public function __construct($scriptData = [])
	{
		$this->db = new DB();
		$this->db_request = isset($scriptData['tovars']) && $scriptData['tovars'] ? $scriptData['tovars'] : null;
		if (isset($scriptData['site'])) $this->site = $scriptData['site'];
	}

	public function init(): void
	{
		if (!$this->db_request) die('tovars not set');
		switch ($this->db_request) {
			case 'vitrina':
			case 'non_flowers':
			case 'multiple_prices':
				$stmt = "SELECT id FROM tovars WHERE $this->db_request = 1";
				break;
			case 'allowed_today':
				$stmt = "SELECT id FROM tovars WHERE (allowed_today = 1 OR vitrina = 1)";
				break;
			case 'columns':
				$stmt = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tovars' AND COLUMN_NAME NOT IN ('title', 'id', 'shop_crm_id')";
				break;
			default:
				die('tovars rule not found');
		}
		//если в запросе передан сайт, добвляем его к query
		if ($this->site) {
			$shopId = $this->getShopId();
			if ($shopId) $stmt .= " AND shop_crm_id = $shopId";
		}

		echo json_encode($this->db->sql($stmt));
	}

	//получаем id магазина
	private function getShopId()
	{
		$response = $this->db->sql("SELECT shop_crm_id FROM shops WHERE crm_shop_code = '$this->site'");
		if (!$response) return null;
		return intval($response[0]->shop_crm_id);
	}
}
