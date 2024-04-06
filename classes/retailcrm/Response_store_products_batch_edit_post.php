<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_store_products_batch_edit_post extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'store/products/batch/edit';
	}

	public function editProductsInCRM(array $args)
	{
		$this->retailcrmArgs = $args;
		$this->request('post');

		$logger = Logger::getInstance();
		$logger->addToLog('store_products_batch_edit_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('store_products_batch_edit_method', $this->retailcrmMethod);
		$logger->addToLog('store_products_batch_edit_args', $this->retailcrmArgs);
		$logger->addToLog('store_products_batch_edit_response', $this->response);

		return $this->response;
	}
}
