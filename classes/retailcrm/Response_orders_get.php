<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_orders_get extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'orders';
	}

	public function getOrdersFromCrm($args)
	{
		$this->retailcrmArgs = $args;
		$this->request('get');

		$logger = Logger::getInstance();
		$logger->addToLog('orders_get_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('orders_get_method', $this->retailcrmMethod);
		$logger->addToLog('orders_get_args', $this->retailcrmArgs);
		$logger->addToLog('orders_get_response', $this->response);
	}

	public function getOrders()
	{
		return $this->response->orders ?: [];
	}
}
