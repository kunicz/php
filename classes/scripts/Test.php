<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\Logger;
use php2steblya\retailcrm\Response_store_products_get;
use php2steblya\retailcrm\Response_store_products_batch_edit_post;
use php2steblya\telegram\Response_sendMessage_post;
use php2steblya\retailcrm\Response_orders_get;

class Test extends Script
{


	public function __construct($scriptData = [])
	{
		$this->db = DB::getInstance();
		$this->logger = Logger::getInstance();
		$this->logger->addToLog('script', Logger::shortenPath(__FILE__));
	}

	public function init()
	{
		$currentMonthFirstDay = strtotime(date('Y-m-01'));
		$createdAtFrom = date('Y-m-d', strtotime('-1 month', $currentMonthFirstDay));
		$createdAtTo = date('Y-m-d', strtotime('-1 day', $currentMonthFirstDay));
		$args = [
			'filter' => [
				'customerId' => 1383,
				'createdAtFrom' => $createdAtFrom,
				'createdAtTo' => $createdAtTo
			]
		];
		$response = new Response_orders_get();
		$response->getOrdersFromCrm($args);
		$ordersOld = $response->getOrders();
		echo json_encode($this->logger->getLogData());
	}
}
