<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\Logger;
use php2steblya\Finish;

class Tilda_products_from_DB extends Script
{
	private $request;
	private $params;

	public function __construct($scriptData = [])
	{
		$this->db = DB::getInstance();
		$this->logger = Logger::getInstance();
		$this->logger->addToLog('script', __CLASS__);
		$this->request = isset($scriptData['products']) && $scriptData['products'] ? $scriptData['products'] : null;
		$this->params = $scriptData;
	}

	public function init(): void
	{
		try {
			if (!$this->request) throw new \Exception("products rule not set");
			switch ($this->request) {
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
					$stmt = "SELECT id FROM products WHERE {$this->where("$this->request = 1")}";
					break;
				case 'vitrina_id':
				case 'date_to_open':
				case 'days_to_close':
				case 'hours_to_produce':
					$stmt = "SELECT id,$this->request FROM products WHERE {$this->where("$this->request IS NOT NULL AND $this->request != 0")}";
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
					throw new \Exception("products rule ($this->request) not found");
			}
			$response = $this->db->sql($stmt);
			if ($this->db->hasError()) throw new \Exception("DB request ($this->request) error for statement ($stmt) " . $this->db->getError());
			Finish::success('fromDB', $response);
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	private function where($string = '')
	{
		if (!isset($this->params['site'])) return ($string ?: 1);
		return ($string ?: 1) . ' AND shop_crm_id = ' . $this->getSiteFromDB(['code' => $this->params['site']])[0]->shop_crm_id;
	}
}
