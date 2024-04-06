<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\retailcrm\Response_store_products_get;
use php2steblya\retailcrm\Response_store_productgroups_get;
use php2steblya\retailcrm\Response_store_productgroups_edit_post;
use php2steblya\retailcrm\Response_store_products_batch_edit_post;

class Disable_product_groups extends Script
{
	private $products;
	private $productGroups;

	public function __construct($scriptData = [])
	{
		$this->logger = Logger::getInstance();
		$this->logger->addToLog('script', Logger::shortenPath(__FILE__));
		$this->site = isset($scriptData['site']) ? $scriptData['site'] : null;
	}

	public function init()
	{
		if (!$this->site) {
			echo 'site not set';
			return;
		}
		if (!$this->isSiteExists()) {
			echo 'site not exists';
			return;
		}

		$this->collectGroups();
		$this->disableGroups();
		$this->collectProducts();
		$this->removeGroupsFromProducts();

		echo json_encode($this->logger->getLogData());
	}

	private function collectGroups()
	{
		$response = new Response_store_productgroups_get();
		$args = [
			'limit' => 100,
			'filter' => [
				'sites' => [$this->site],
				'active' => true
			]
		];
		$response->getProductGroupsFromCRM($args);
		$this->productGroups = $response->getProductGroups();
	}

	private function disableGroups()
	{
		if (empty($this->productGroups)) return;
		foreach ($this->productGroups as $productGroup) {
			$args = [
				'by' => 'id',
				'site' => $this->site,
				'productGroup' => json_encode(['active' => false])
			];
			$response = new Response_store_productgroups_edit_post($productGroup->id);
			$response->editProductGroupInCRM($args);
		}
	}

	private function collectProducts()
	{
		$response = new Response_store_products_get();
		$args = [
			'limit' => 100,
			'filter' => [
				'sites' => [$this->site]
			]
		];
		$response->getProductsFromCRM($args);
		$this->products = $response->getProducts();
	}

	private function removeGroupsFromProducts()
	{
		if (empty($this->products)) return;
		$productsArgs = [];
		$i = 0;
		foreach ($this->products as $product) {
			$productsArgs[] = [
				'id' => $product->id,
				'site' => $this->site,
				'groups' => []
			];
			$i++;
			if ($i == 50) break; //метод не принимает больше 50 товаров
		}
		$response = new Response_store_products_batch_edit_post();
		$response->editProductsInCRM(['products' => json_encode($productsArgs)]);
	}
}
