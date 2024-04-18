<?

namespace php2steblya\scripts;

use php2steblya\Finish;
use php2steblya\retailcrm\Response_orders_get;
use php2steblya\retailcrm\Response_orders_edit_post;
use php2steblya\retailcrm\Response_orders_create_post;

class Spisanie_every_month extends Script
{
	private $customerId = 1383;

	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			foreach (['ostatki-msk'] as $site) {
				$this->site = $site;
				$this->spisanieOld();
				$this->spisanieNew();
				Finish::success();
			}
		} catch (\Exception $e) {
			Finish::fail($e);
		}
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
