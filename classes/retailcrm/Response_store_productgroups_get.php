<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_store_productgroups_get extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'store/product-groups';
	}

	public function getProductGroupsFromCRM(array $args)
	{
		$this->retailcrmArgs = $args;
		$this->request('get');

		$logger = Logger::getInstance();
		$logger->addToLog('store_productgroups_get_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('store_productgroups_get_method', $this->retailcrmMethod);
		$logger->addToLog('store_productgroups_get_args', $this->retailcrmArgs);
		$logger->addToLog('store_productgroups_get_response', $this->response);
	}

	public function getProductGroups()
	{
		return $this->response->productGroup ?: [];
	}
}
