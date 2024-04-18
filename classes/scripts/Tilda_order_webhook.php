<?

namespace php2steblya\scripts;

use php2steblya\File;
use php2steblya\Finish;
use php2steblya\order\Order;
use php2steblya\retailcrm\Response_customers_get;

class Tilda_order_webhook extends Script
{
	private array $orderData;

	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			if ($this->isTest()) {
				//здесь я могу отключать то, что мне не надо тестировать
				//$db = false;
				//$crm = false;
				//$telegram = false;
				$this->logger->addToLog('test', true);
			} else {
				$db = true;
				$crm = true;
				$telegram = true;
			}
			$this->orderData();
			$order = $this->order();
			if (!$this->isTildaTest()) {
				if ($db) $order->saveToDB();
				if ($this->isOrderPayed()) {
					if ($telegram) $order->sendToTelegramChannel();
					if ($crm) $order->sendToCrm();
					$this->logger->addToLog('paid', $this->isOrderPayed());
				}
				if ($this->isWriteTest()) $this->writeOrderInTestFile();
			} else {
				$this->logger->addToLog('tildaTest', true);
			}

			http_response_code(200);
			Finish::success();
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	private function isOrderPayed()
	{
		return isset($this->orderData['payment']['systranid']);
	}

	private function isTest()
	{
		return isset($this->scriptData['test']);
	}

	private function isTildaTest()
	{
		return !isset($this->orderData['formid']);
	}

	private function isWriteTest()
	{
		return isset($this->scriptData['write']);
	}

	private function orderData()
	{
		if (!$this->site) throw new \Exception('Tilda_order_webhook : site not set');
		if (!$this->isSiteExists()) throw new \Exception('Tilda_order_webhook : site (' . $this->site . ') not found');

		$this->orderData = $_POST;
		$this->orderData['site'] = $this->site;
		$this->orderData['date'] = date('Y-m-d H:i:s');
		if (!isset($this->orderData['city_id'])) $this->orderData['city_id'] = "1";
		if (!isset($this->orderData['customer_crm_id'])) $this->orderData['customer_crm_id'] = $this->getCustomerCrmId();
		if (!isset($this->orderData['shop_crm_id'])) $this->orderData['shop_crm_id'] = $this->getSiteFromDB(['code' => $this->site])[0]->shop_crm_id;
	}

	private function order()
	{
		if ($this->isTest()) {
			$file = new File(dirname(dirname(dirname(__FILE__))) . '/tilda_test_order.json');
			$testOrderData = json_decode($file->getContents(), true);
			foreach ($testOrderData as $key => $field) {
				$this->orderData[$key] = $field;
			}
			$this->orderData['customer_crm_id'] = $this->getCustomerCrmId();
		}
		$this->logger->addToLog('orderData', $this->orderData);
		return new Order($this->orderData);
	}

	private function getCustomerCrmId()
	{
		if (!isset($this->orderData['phone-zakazchika'])) return null;
		$response = new Response_customers_get();
		$args = [
			'filter' => [
				'name' => $this->orderData['phone_zakazchika']
			]
		];
		$response->getCustomersFromCrm($args);
		if (empty($response->getCustomers())) return null;
		return $response->getCustomers()[0]->id;
	}

	private function writeOrderInTestFile()
	{
		$file = new File(dirname(dirname(dirname(__FILE__))) . '/tilda_test_order.json');
		$file->write(json_encode($this->orderData, JSON_PRETTY_PRINT));
	}
}
