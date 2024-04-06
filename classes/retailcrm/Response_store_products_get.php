<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;

class Response_store_products_get extends Response
{
	public function __construct()
	{
		$this->retailcrmMethod = 'store/products';
	}

	public function getProductsFromCRM(array $args)
	{
		$this->retailcrmArgs = $args;
		$this->request('get');

		$logger = Logger::getInstance();
		$logger->addToLog('store_products_get_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('store_products_get_method', $this->retailcrmMethod);
		$logger->addToLog('store_products_get_args', $this->retailcrmArgs);
		$logger->addToLog('store_products_get_response', $this->response);
	}

	public function getProducts()
	{
		return $this->response->products ?: [];
	}
}
