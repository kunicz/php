<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_orders_create_post extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'orders/create';
	}

	public function createOrderInCRM($args)
	{
		$this->retailcrmArgs = $args;
		$this->request('post');

		$logger = Logger::getInstance();
		$logger->addToLog('orders_create_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('orders_create_method', $this->retailcrmMethod);
		$logger->addToLog('orders_create_args', $this->retailcrmArgs);
		$logger->addToLog('orders_create_response', $this->response);

		return $this->response;
	}

	public function getOrderId()
	{
		return $this->response->id;
	}
}
