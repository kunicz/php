<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_reference_stores_get extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'reference/stores';
	}

	public function getStoresFromCRM(array $args = [])
	{
		$this->retailcrmArgs = $args;
		$this->request('get');

		$logger = Logger::getInstance();
		$logger->addToLog('stores_get_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('stores_get_method', $this->retailcrmMethod);
		$logger->addToLog('stores_get_args', $this->retailcrmArgs);
		$logger->addToLog('stores_get_response', $this->response);
	}

	public function getStores()
	{
		return $this->response->stores ?: [];
	}
}
