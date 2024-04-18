<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\Logger;
use php2steblya\Finish;
use php2steblya\retailcrm\Response_store_products_get;
use php2steblya\retailcrm\Response_store_products_batch_edit_post;
use php2steblya\telegram\Response_sendMessage_post;
use php2steblya\retailcrm\Response_orders_get;

class Test extends Script
{
	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		$args = [
			'filter' => [
				'ids' => [3201],
			]
		];
		$response = new Response_orders_get();
		$response->getOrdersFromCrm($args);
		if ($response->hasError()) throw new \Exception($response->getError());
		$this->logger->addToLog('order', $response->getOrders());
		Finish::success();
	}
}
