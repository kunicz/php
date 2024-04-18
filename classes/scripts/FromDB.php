<?

namespace php2steblya\scripts;

use php2steblya\Finish;
use php2steblya\retailcrm\Response_store_products_get;

class FromDB extends Script
{
	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			if (empty($this->scriptData)) throw new \Exception('no parameters passed');
			if (!isset($this->scriptData['request']) || !$this->scriptData['request']) throw new \Exception("request not set");
			$request = $this->scriptData['request'];
			switch ($request) {
				case 'shops':
					$stmt = "SELECT * FROM shops";
					break;
				case 'dopniki':
					$stmt = "SELECT * FROM products WHERE type = '888'";
					break;
				case 'type':
				case 'purchase_price':
					if (!isset($this->scriptData['id']) || !$this->scriptData['id']) throw new \Exception("id note set");
					$crmId = $this->scriptData['id'];
					//надо получить id товара в Тильде
					$args = [
						'limit' 	=> 100,
						'page'		=> 1,
						'filter' 	=> [
							'ids' => [$crmId],
						]
					];
					$response = new Response_store_products_get();
					$response->getProductsFromCRM($args);
					$products = $response->getProducts();
					if (empty($products)) throw new \Exception("crm products with id ($crmId) not found");
					$tildaId = $products[0]->externalId;

					$stmt = "SELECT $request FROM products WHERE id = '$tildaId'";
					break;
				default:
					throw new \Exception("rule for parameter ($request) not found");
			}
			$response = $this->db->sql($stmt);
			if ($this->db->hasError()) throw new \Exception("DB request ($request) error for statement ($stmt) " . $this->db->getError());
			Finish::success('response', $response);
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}
}
