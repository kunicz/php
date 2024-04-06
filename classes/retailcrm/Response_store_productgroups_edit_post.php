<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_store_productgroups_edit_post extends Response
{

	private $productGroupId;

	public function __construct($productGroupId)
	{
		$this->productGroupId = $productGroupId;
		$this->retailcrmMethod = 'store/product-groups/' . $productGroupId . '/edit';
	}

	public function editProductGroupInCRM(array $args)
	{
		$this->retailcrmArgs = $args;
		$this->request('post');

		$logger = Logger::getInstance();
		$logger->addToLog('store_productgroup_' . $this->productGroupId . '_edit_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('store_productgroup_' . $this->productGroupId . '_edit_method', $this->retailcrmMethod);
		$logger->addToLog('store_productgroup_' . $this->productGroupId . '_edit_args', $this->retailcrmArgs);
		$logger->addToLog('store_productgroup_' . $this->productGroupId . '_edit_response', $this->response);

		return $this->response;
	}
}
