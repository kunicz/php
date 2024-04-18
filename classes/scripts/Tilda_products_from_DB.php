<?

namespace php2steblya\scripts;

use php2steblya\Finish;

class Tilda_products_from_DB extends Script
{
	public function init(): void
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			$request = isset($this->scriptData['products']) && $this->scriptData['products'] ? $this->scriptData['products'] : null;
			if (!$request) throw new \Exception("products rule not set");
			switch ($request) {
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
					$stmt = "SELECT id FROM products WHERE {$this->where("$request = 1")}";
					break;
				case 'vitrina_id':
				case 'date_to_open':
				case 'days_to_close':
				case 'hours_to_produce':
					$stmt = "SELECT id,$request FROM products WHERE {$this->where("$request IS NOT NULL AND $request != 0")}";
					break;
				case 'card_type':
					$stmt = "SELECT id FROM products";
					if (!isset($this->scriptData['card_type'])) {
						$stmt .= " WHERE card_type != 'no'";
					} else {
						$cardType = $this->scriptData['card_type'];
						$stmt .= " WHERE card_type = '$cardType'";
					}
					break;
				case 'new':
					$stmt = "SELECT id,createdOn,title FROM products WHERE {$this->where("type IS NULL AND (id != vitrina_id OR vitrina_id IS NULL) AND createdOn >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 MONTH)")} ORDER BY createdOn DESC";
					break;
				default:
					throw new \Exception("products rule ($request) not found");
			}
			$response = $this->db->sql($stmt);
			if ($this->db->hasError()) throw new \Exception("DB request ($request) error for statement ($stmt) " . $this->db->getError());
			Finish::success('fromDB', $response);
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	private function where($string = '')
	{
		if (!isset($this->scriptData['site'])) return ($string ?: 1);
		return ($string ?: 1) . ' AND shop_crm_id = ' . $this->getSiteFromDB(['code' => $this->scriptData['site']])[0]->shop_crm_id;
	}
}
