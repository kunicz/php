<?

namespace php2steblya\scripts;

use php2steblya\Finish;
use php2steblya\retailcrm\Response_store_products_get;
use php2steblya\retailcrm\Response_store_productgroups_get;
use php2steblya\retailcrm\Response_store_productgroups_edit_post;
use php2steblya\retailcrm\Response_store_products_batch_edit_post;

class Disable_product_groups extends Script
{
	private $products;
	private $productGroups;

	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			if (!$this->site) throw new \Exception('site not set');
			if (!$this->isSiteExists()) throw new \Exception('site not exists');

			$this->collectGroups();
			$this->disableGroups();
			$this->collectProducts();
			$this->removeGroupsFromProducts();

			Finish::success();
		} catch (\Exception $e) {
			Finish::fail($e);
		}
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
		if ($response->hasError()) throw new \Exception($response->getError());
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
			if ($response->hasError()) throw new \Exception($response->getError());
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
		if ($response->hasError()) throw new \Exception($response->getError());
		$this->products = $response->getProducts();
	}

	private function removeGroupsFromProducts()
	{
		if (empty($this->products)) throw new \Exception("no products found");
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
		if ($response->hasError()) throw new \Exception($response->getError());
	}
}
