<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_orders_edit_post extends Response
{
	private $orderId;

	public function __construct($orderId)
	{
		$this->orderId = $orderId;
		$this->retailcrmMethod = 'orders/' . $orderId . '/edit';
	}

	public function editOrderInCrm($args)
	{
		$this->retailcrmArgs = $args;
		$this->request('post');

		$logger = Logger::getInstance();
		$logger->addToLog('order_' . $this->orderId . 'edit_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('order_' . $this->orderId . 'edit_method', $this->retailcrmMethod);
		$logger->addToLog('order_' . $this->orderId . 'edit_args', $this->retailcrmArgs);
		$logger->addToLog('order_' . $this->orderId . 'edit_response', $this->response);

		return $this->response;
	}
}
