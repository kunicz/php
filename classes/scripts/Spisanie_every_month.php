<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\Logger;
use php2steblya\retailcrm\Response_orders_get;
use php2steblya\retailcrm\Response_orders_edit_post;
use php2steblya\retailcrm\Response_orders_create_post;

class Spisanie_every_month extends Script
{
	private $customerId;

	public function __construct($scriptData = [])
	{
		$this->db = DB::getInstance();
		$this->logger = Logger::getInstance();
		$this->customerId = 1383;
		$this->site = 'ostatki';
	}

	public function init()
	{
		$this->spisanieOld();
		$this->spisanieNew();

		echo json_encode($this->logger->getLogData());
	}

	private function spisanieOld()
	{
		$currentMonthFirstDay = strtotime(date('Y-m-01'));
		$createdAtFrom = date('Y-m-d', strtotime('-1 month', $currentMonthFirstDay));
		$createdAtTo = date('Y-m-d', strtotime('-1 day', $currentMonthFirstDay));
		$args = [
			'filter' => [
				'customerId' => $this->customerId,
				'createdAtFrom' => $createdAtFrom,
				'createdAtTo' => $createdAtTo
			]
		];
		$response = new Response_orders_get();
		$response->getOrdersFromCrm($args);
		$ordersOld = $response->getOrders();
		$this->logger->addToLog('spisanie_old', $ordersOld);
		if (empty($ordersOld)) return;
		$args = [
			'by' => 'id',
			'site' => $this->site,
			'order' => json_encode(['status' => 'complete'])
		];
		foreach ($ordersOld as $orderOld) {
			$response = new Response_orders_edit_post($orderOld->id);
			$response->editOrderInCrm($args);
		}
	}

	private function spisanieNew()
	{
		$orderData = [
			'site' => $this->site,
			'firstName' => 'списание',
			'costumer' => [
				'id' => $this->customerId
			],
			'delivery' => [
				'date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-t'))))
			],
			'customFields' => [
				'florist' => 'boss'
			]
		];
		$args = [
			'site' => $this->site,
			'order' => json_encode($orderData)
		];
		$response = new Response_orders_create_post();
		$response->createOrderInCRM($args);
	}
}
