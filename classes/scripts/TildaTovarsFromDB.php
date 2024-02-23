<?

namespace php2steblya\scripts;

use php2steblya\DB;

class TildaTovarsFromDB
{
	private $db;
	private $db_request;
	private $params;

	public function __construct($scriptData = [])
	{
		$this->db = new DB();
		$this->db_request = isset($scriptData['tovars']) && $scriptData['tovars'] ? $scriptData['tovars'] : null;
		$this->params = $scriptData;
	}

	public function init(): void
	{
		if (!$this->db_request) die('tovars not set');
		switch ($this->db_request) {
			case 'hidden':
			case 'vitrina':
			case 'dopnik':
			case 'paid_delivery':
			case 'multiple_prices':
				$stmt = "SELECT id FROM tovars WHERE $this->db_request = 1";
				break;
			case 'hours_to_produce':
			case 'date_to_open':
			case 'days_to_close':
				$stmt = "SELECT id,$this->db_request FROM tovars WHERE $this->db_request IS NOT NULL AND $this->db_request != 0";
				break;
			case 'allowed_today':
				$stmt = "SELECT id FROM tovars WHERE (allowed_today = 1 OR vitrina = 1)";
				break;
			case 'card_types':
				$stmt = "SELECT DISTINCT card_type FROM tovars WHERE card_type IS NOT NULL";
				break;
			case 'card_type':
				$stmt = "SELECT id FROM tovars";
				if (!isset($this->params['card_type'])) {
					$stmt .= " WHERE card_type != 'no'";
				} else {
					$cardType = $this->params['card_type'];
					$stmt .= " WHERE card_type = '$cardType'";
				}
				break;
			case 'days_to_close':
				$stmt = "SELECT id,days_to_close FROM tovars WHERE date_to_open IS NOT NULL";
				break;
			case 'columns':
				$stmt = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tovars' AND COLUMN_NAME NOT IN ('title', 'id', 'shop_crm_id','hours_to_produce','card_type')";
				break;
			default:
				die('tovars rule not found');
		}
		//если в запросе передан сайт, добвляем его к query
		if (isset($this->params['site'])) {
			$shopId = $this->getShopId();
			if ($shopId) $stmt .= " AND shop_crm_id = $shopId";
		}

		echo json_encode($this->db->sql($stmt));
	}

	//получаем id магазина
	private function getShopId()
	{
		$site = $this->params['site'];
		$response = $this->db->sql("SELECT shop_crm_id FROM shops WHERE crm_shop_code = '$site'");
		if (!$response) return null;
		return intval($response[0]->shop_crm_id);
	}
}
