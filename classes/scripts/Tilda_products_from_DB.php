<?

namespace php2steblya\scripts;

use php2steblya\DB;

class Tilda_products_from_DB extends Script
{
	private $db_request;
	private $params;

	public function __construct($scriptData = [])
	{
		$this->db = DB::getInstance();
		$this->db_request = isset($scriptData['products']) && $scriptData['products'] ? $scriptData['products'] : null;
		$this->params = $scriptData;
	}

	public function init(): void
	{
		if (!$this->db_request) die('products not set');
		switch ($this->db_request) {
			case 'all':
				$stmt = "SELECT * FROM products WHERE {$this->where()}";
				break;
			case 'hidden':
			case 'fixed_price':
			case 'select_color':
			case 'select_gamma':
			case 'allowed_today':
			case 'random_sostav':
			case 'paid_delivery':
			case 'multiple_prices':
				$stmt = "SELECT id FROM products WHERE {$this->where("$this->db_request = 1")}";
				break;
			case 'vitrina_id':
			case 'date_to_open':
			case 'days_to_close':
			case 'hours_to_produce':
				$stmt = "SELECT id,$this->db_request FROM products WHERE {$this->where("$this->db_request IS NOT NULL AND $this->db_request != 0")}";
				break;
			case 'card_type':
				$stmt = "SELECT id FROM products";
				if (!isset($this->params['card_type'])) {
					$stmt .= " WHERE card_type != 'no'";
				} else {
					$cardType = $this->params['card_type'];
					$stmt .= " WHERE card_type = '$cardType'";
				}
				break;
			case 'new':
				$stmt = "SELECT id,createdOn,title FROM products WHERE {$this->where("type IS NULL AND (id != vitrina_id OR vitrina_id IS NULL) AND createdOn >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 MONTH)")} ORDER BY createdOn DESC";
				break;
			default:
				die('products rule not found');
		}

		echo json_encode($this->db->sql($stmt));
	}

	private function where($string = '')
	{
		if (!isset($this->params['site'])) return ($string ?: 1);
		return ($string ?: 1) . ' AND shop_crm_id = ' . $this->getSiteFromDB(['code' => $this->params['site']])[0]->shop_crm_id;
	}
}
