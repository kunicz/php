<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_customers_get extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'customers';
	}

	public function getCustomersFromCrm($args)
	{
		$this->retailcrmArgs = $args;
		$this->request('get');

		$logger = Logger::getInstance();
		$logger->addToLog('customers_get_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('customers_get_method', $this->retailcrmMethod);
		$logger->addToLog('customers_get_args', $this->retailcrmArgs);
		$logger->addToLog('customers_get_response', $this->response);
	}

	public function getCustomers()
	{
		return $this->response->customers ?: [];
	}
}
