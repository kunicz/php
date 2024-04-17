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
		$this->logger->addToLog('script', __CLASS__);
	}

	public function init()
	{
	}
}
