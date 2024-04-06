<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_customers_edit_post extends Response
{
	private $customerId;

	public function __construct($customerId)
	{
		$this->customerId = $customerId;
		$this->retailcrmMethod = 'customers/' . $customerId . '/edit';
	}

	public function editCustomerInCrm($args)
	{
		$this->retailcrmArgs = $args;
		$this->request('post');

		$logger = Logger::getInstance();
		$logger->addToLog('customer_' . $this->customerId . '_edit_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('customer_' . $this->customerId . '_edit_method', $this->retailcrmMethod);
		$logger->addToLog('customer_' . $this->customerId . '_edit_args', $this->retailcrmArgs);
		$logger->addToLog('customer_' . $this->customerId . '_edit_response', $this->response);

		return $this->response;
	}
}
