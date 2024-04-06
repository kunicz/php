<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_store_inventories_upload_post extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'store/inventories/upload';
	}

	public function uploadInventoriesToCRM($args)
	{
		$this->retailcrmArgs = $args;
		$this->request('post');

		$logger = Logger::getInstance();
		$logger->addToLog('store_inventories_upload_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('store_inventories_upload_method', $this->retailcrmMethod);
		$logger->addToLog('store_inventories_upload_args', $this->retailcrmArgs);
		$logger->addToLog('store_inventories_upload_response', $this->response);

		return $this->response;
	}
}
