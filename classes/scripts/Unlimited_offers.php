<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\Finish;
use php2steblya\retailcrm\Response_store_products_get;
use php2steblya\retailcrm\Response_reference_stores_get;
use php2steblya\retailcrm\Response_store_inventories_upload_post;

class Unlimited_offers extends Script
{
	private array $products = [];
	private array $offers = [];

	public function init()
	{
		$this->logger = Logger::getInstance();
		$this->logger->addToLog('script', __CLASS__);

		try {
			$this->collectProducts();
			$this->collectOffers();
			$this->fillInventories();
			Finish::success();
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	private function collectProducts($page = 1)
	{
		$args = [
			'limit' 	=> 100,
			'page'		=> $page,
			'filter' 	=> [
				'sites' => $this->getSitesFromDB('code'),
			]
		];
		$response = new Response_store_products_get();
		$response->getProductsFromCRM($args);
		if ($response->hasError()) throw new \Exception($response->getError());
		$this->logger->addToLog('unlimited_offers_total_page_count', $response->getTotalPageCount());
		if (empty($response->getProducts())) return;
		foreach ($response->getProducts() as $product) {
			$this->products[] = $product;
		}
		//$this->logger->addToLog('page_' . $page, $products);
		if ($page >= $response->getTotalPageCount()) return;
		$this->collectProducts($page + 1);
	}

	private function collectOffers()
	{
		if (empty($this->products)) return;
		$this->logger->addToLog('unlimited_offers_products', $this->products);
		$offersIds = [];
		$stores = $this->activeCrmStores();
		foreach ($this->products as $product) {
			foreach ($product->offers as $offer) {
				if ($offer->quantity) continue;
				$offersIds[] = [$offer->id, $offer->name];
				$this->offers[] = [
					'id' => $offer->id,
					'stores' => $stores
				];
			}
		}
		$this->logger->addToLog('unlimited_offers', $offersIds ?: 'none');
	}

	private function fillInventories()
	{
		if (empty($this->offers)) return;
		$response = new Response_store_inventories_upload_post();
		$response->uploadInventoriesToCRM(['offers' => json_encode($this->offers)]);
		if ($response->hasError()) throw new \Exception($response->getError());
	}

	private function activeCrmStores()
	{
		$stores = [];
		$available = 99999;
		$response = new Response_reference_stores_get();
		$response->getStoresFromCRM();
		$storesFromCrm = $response->getStores();
		foreach ($storesFromCrm as $store) {
			if (!$store->active) continue;
			$stores[] = [
				'code' => $store->code,
				'available' => $available
			];
		}
		$this->logger->addToLog('unlimited_offers_active_stores', $stores);
		return $stores;
	}
}
