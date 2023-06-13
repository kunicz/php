<?

namespace php2steblya\scripts;

use php2steblya\File;
use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiRetailCrm as Api;
use php2steblya\LoggerException as Exception;

class TildaOrderWebhook
{
	public $log;
	private $site;
	private array $filePaths;
	private $orderData;
	private $orderId;
	private $customerId;

	public function __construct($site, $testMode = false)
	{
		$this->site = $site;
		$this->log = new Logger('tilda orders webhook');
		$this->filePaths = [
			'orderTest.json' => dirname(dirname(dirname(__FILE__))) . '/testOrder.json',
			'orders.txt' => dirname(dirname(dirname(__FILE__))) . '/TildaOrders_' . $site . '.txt',
			'orderLast.txt' => dirname(dirname(dirname(__FILE__))) . '/TildaOrderLast_' . $site . '.txt',
		];
		if ($testMode) {
			$testOrderFile = new File($this->filePaths['orderTest.json']);
			$_POST = json_decode($testOrderFile->getContents(), true);
		}
		$this->orderData = new OrderData($this->site);
		$this->orderData->fromTilda($_POST);
	}
	public function init(): void
	{
		$this->searchCustomer($this->orderData->zakazchik->phone);
		$this->crmOrder();
		$this->crmCustomer();
		$this->appendOrderToFile();
		$this->orderLastToFile();
		$this->log->setRemark('created order (' . $this->orderId . ') for ' . $_POST['name-zakazchika'] . ' (' . $this->customerId . ')');
		$this->log->writeSummary();
	}
	public function searchCustomer($phone)
	{
		try {
			$this->log->insert('search customer by phone');
			$args = [
				'filter' => [
					'name' => $phone
				]
			];
			$api = new Api();
			$api->get('customers', $args);
			$this->log->insert('1. search customer');
			$this->log->push('phone', $phone);
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
			if (!$api->getCount()) {
				$this->log->pushNote('customer not found (' . $phone . ')');
				$this->customerId = '';
			} else {
				$this->customerId = $api->response->customers[0]->id;
			}
			$this->orderData->setCustomerId($this->customerId);
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
	private function crmOrder()
	{
		try {
			$args = [
				'site' => $this->site,
				'order' => $this->orderData->getCrm()
			];
			$api = new Api();
			$api->post('orders/create', $args);
			$this->log->insert('2. create order');
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			$this->log->push('orderData', $this->orderData->getCrm(false));
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
			$this->orderId = $api->response->order->id;
			$this->customerId = $api->response->order->customer->id;
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
	private function crmCustomer()
	{
		try {
			$customerData = $this->customerData();
			$args = [
				'by' => 'id',
				'site' => $this->site,
				'customer' => json_encode($customerData)
			];
			$api = new Api();
			$api->post('customers/' . $this->customerId . '/edit', $args);
			$this->log->insert('3. modify customer');
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
	private function customerData()
	{
		/**
		 * если клиент уже есть в базе, то не переписываем "откуда узнал"
		 */
		$customerData = [
			'address' => [
				'text' => ''
			],
			'customFields' => [
				'telegram' => $this->orderData->zakazchik->telegram,
				'ya_client_id' => $this->orderData->analytics->yandex['clientId'],
			]
		];
		if ($this->customerId) return $customerData;
		$customerData['customFields']['otkuda_uznal_o_nas'] = $this->orderData->analytics->otkudaUznal;
		return $customerData;
	}
	private function appendOrderToFile()
	{
		$ordersFile = new File($this->filePaths['orders.txt']);
		$orders = $ordersFile->getContents();
		if ($orders) {
			$orders = json_decode($orders, true);
		} else {
			$orders = [];
		}
		$orders[] = $_POST;
		$ordersFile->write(json_encode($orders));
	}
	private function orderLastToFile()
	{
		$orderLastFile = new File($this->filePaths['orderLast.txt']);
		$orderLastFile->write(print_r($_POST, true));
		$products = [];
		foreach ($_POST['payment']['products'] as $product) {
			$products[] = $product['name'];
		}
		$this->log->setRemark($_POST['name-zakazchika'] . ' / ' . implode(',', $products));
		$this->log->writeSummary();
	}
}
