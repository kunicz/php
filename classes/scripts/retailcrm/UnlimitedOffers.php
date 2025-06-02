<?php

namespace php2steblya\scripts\retailcrm;

use php2steblya\Exception;
use php2steblya\Script;

// класс для работы с неограниченными предложениями в RetailCRM.
class UnlimitedOffers extends Script
{
	private array $products = [];
	private array $stores = [];
	private array $offersToUpdate = [];

	public function init(): void
	{
		$this->collectProducts();
		$this->logger->setGroup("все продукты")->add('products', $this->products);

		$this->logger->setGroup('активные склады');
		$this->collectActiveStores();

		$this->logger->setGroup('все офферы');
		$this->collectOffers();

		if (empty($this->offersToUpdate)) return;

		$this->logger->setGroup("изменяем офферы");
		$this->updateInventories();
	}

	// собирает все товары из retailCrm рекурсивно.
	private function collectProducts(int $page = 1): void
	{
		$this->logger->setGroup("получаем продукты. page $page");

		$args = [
			'page'   => $page,
			'filter' => ['sites' => array_column($this->shops, 'shop_crm_code'),]
		];
		$apiResponse = $this->retailcrm->products()->get($args);
		$pagination = $apiResponse->pagination;
		$products = $apiResponse->products;
		if (empty($products)) return;

		foreach ($products as $product) {
			$this->products[] = $product;
		}

		if ($page >= $pagination->totalPageCount) return;

		$this->collectProducts($page + 1);
	}

	// собирает все активные склады.
	private function collectActiveStores(): void
	{
		$available = 99999;
		$apiResponse = $this->retailcrm->stores()->getActive();
		if (empty($apiResponse->stores)) throw new \Exception('не найдено ни одного активного склада');
		foreach ($apiResponse->stores as $store) {
			$stores[] = [
				'code' => $store->code,
				'available' => $available
			];
		}
		$this->stores = $stores;
		$this->logger->add('stores', $this->stores);
	}

	// определяет и собирает все офферы, которые необходимо обезлимитить.
	private function collectOffers(): void
	{
		if (empty($this->products)) return;

		$allOffers = [];
		foreach ($this->products as $product) {
			$allOffers[] = $product->offers;
			foreach ($product->offers as $offer) {
				if ($offer->quantity > 100) continue;
				$this->offersToUpdate[] = [
					'id' => $offer->id,
					'stores' => $this->stores
				];
			}
		}

		$this->logger
			->add('all', $allOffers)
			->add('to_update', $this->offersToUpdate);
	}

	// обновляет офферы.
	private function updateInventories(): void
	{
		if (empty($this->offersToUpdate)) return;

		$this->retailcrm->inventories()->upload($this->offersToUpdate);
	}
}
