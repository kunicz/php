<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\File;
use php2steblya\Logger;
use php2steblya\order\Order;
use php2steblya\retailcrm\Response_customers_get;

class Tilda_order_webhook extends Script
{
	private array $orderData;
	private array $scriptData;

	public function __construct($scriptData = [])
	{
		try {
			$this->db = DB::getInstance();
			$this->logger = Logger::getInstance();

			$this->scriptData = $scriptData;

			if (!isset($this->scriptData['site'])) throw new \Exception('Tilda_order_webhook : site not set');
			$this->site = $this->scriptData['site'];
			if (!$this->isSiteExists()) throw new \Exception('Tilda_order_webhook : site (' . $this->site . ') not found');

			if (!isset($this->scriptData['DB'])) $this->scriptData['DB'] = true;
			if (!isset($this->scriptData['crm'])) $this->scriptData['crm'] = true;
			if (!isset($this->scriptData['telegram'])) $this->scriptData['telegram'] = true;

			$this->orderData = $_POST;
			$this->orderData['site'] = $this->site;
			$this->orderData['date'] = date('Y-m-d H:i:s');
			$this->orderData['customer_crm_id'] = $this->getCustomerCrmId();
			if (!isset($this->orderData['city_id'])) $this->orderData['city_id'] = "1";
			if (!isset($this->orderData['shop_crm_id'])) $this->orderData['shop_crm_id'] = $this->getSiteFromDB(['code' => $this->site])[0]->shop_crm_id;
		} catch (\Exception $e) {
			$this->logger->addToLog('error_message', $e->getMessage());
			$this->logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			$this->logger->sendToAdmin();
			die();
		}
	}

	public function init()
	{
		$this->logger->addToLog('script', Logger::shortenPath(__FILE__));

		//$this->writeOrderInTestFile();
		$this->checkTest();
		$order = $this->order();
		if ($this->scriptData['DB']) $order->saveToDB();
		if ($this->isOrderPayed()) {
			if ($this->scriptData['telegram']) $order->sendToTelegramChannel();
			if ($this->scriptData['crm']) $order->sendToCrm();
		}

		$this->logger->addToLog('orderData', $this->orderData);
		$this->logger->addToLog('paid', $this->isOrderPayed());

		http_response_code(200);
		echo json_encode($this->logger->getLogData());
	}

	private function isOrderPayed()
	{
		return isset($this->orderData['payment']['systranid']);
	}

	private function checkTest()
	{
		$conditions = [
			isset($this->orderData['test']), // при привязке вебхука тильда отправляет запрос с $_POST['test'=>'test']
			!isset($this->orderData['formid']) // при удачном завершении заказа тильда отправляет массив, в котором всегда есть "formid"
		];
		$this->scriptData['test'] = in_array(true, $conditions);
		if (!$this->scriptData['test']) return;
		//здесь я могу отключать то, что мне не надо тестировать
		//$this->scriptData['DB'] = false;
		//$this->scriptData['crm'] = false;
		//$this->scriptData['telegram'] = false;
	}

	private function order()
	{
		if (!$this->scriptData['test']) {
			$order = new Order($this->orderData);
		} else {
			$file = new File(dirname(dirname(dirname(__FILE__))) . '/tilda_test_order.json');
			$order = json_decode($file->getContents(), true);
			foreach ($order as $key => $field) {
				$this->orderData[$key] = $field;
			}
			$this->orderData['customer_crm_id'] = $this->getCustomerCrmId();
		}
		$this->logger->addToLog('test_mode', $this->scriptData['test']);
		return $order;
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
