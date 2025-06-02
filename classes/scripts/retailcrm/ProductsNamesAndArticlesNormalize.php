<?php

namespace php2steblya\scripts\retailcrm;

use php2steblya\Script;

// скрипт проходит по всем каталожным товарам в retailcrm и нормализует их названия
class ProductsNamesAndArticlesNormalize extends Script
{
	private array $productsFromCrm;
	private array $productsFromYml;
	private array $productsToEdit;
	private array $shop;

	public function init(): void
	{
		$shops = $this->collectSitesFromDb();
		foreach ($shops as $shop) {
			$this->shop = $shop;
			$this->productsFromCrm = [];
			$this->productsFromYml = [];
			$this->productsToEdit = [];

			$this->collectProductsFromCrm();
			$this->collectProductsFromYML();

			if (empty($this->productsFromCrm) || empty($this->productsFromYml)) return;

			$this->collectProductsToEdit();
			$this->editProducts();
		}
	}

	// получаем данные магазина или всех магазинов из бд.
	private function collectSitesFromDb(): array
	{
		return $this->site ? array_filter($this->shops, fn($shop) => $shop['shop_crm_code'] == $this->site) : $this->shops;
	}

	// получаем продукты из CRM для заданного магазина.
	// сохраняем их в $this->productsFromCrm
	private function collectProductsFromCrm(int $page = 1): void
	{
		$this->logger->setGroup("{$this->shop['shop_crm_code']}. получаем продукты. page $page");
		$args = [
			'page' => $page,
			'filter' => [
				'sites' => [
					$this->shop['shop_crm_code']
				]
			]
		];
		try {
			$apiResponse = $this->retailcrm->products()->get($args);
		} catch (\Exception $e) {
			return;
		};

		$pagination = $apiResponse->pagination;
		$products = $apiResponse->products;

		if (empty($products)) return;

		foreach ($products as $product) {
			$this->productsFromCrm[] = $product;
		}

		if ($page >= $pagination->totalPageCount) return;

		$this->collectProductsFromCrm($page + 1);
	}

	// получаем продукты из YML-каталога для заданного магазина.
	// сохраняем их в $this->productsFromYml
	private function collectProductsFromYML(): void
	{
		$this->logger->setGroup("{$this->shop['shop_crm_code']}. получаем продукты из yml");
		$args = [
			'fields' => [
				'catalog'
			],
			'where' => [
				'shop_crm_id' => $this->shop['shop_crm_id']
			],
			'limit' => 1
		];
		$catalog = $this->db->tilda_yml_catalog()->get($args);
		$this->logger->add('catalog', $catalog);

		if (empty($catalog)) return;

		$catalog = json_decode($catalog, true);
		$this->logger->add('catalog', $catalog);

		$this->productsFromYml = $catalog['offers'];
		$this->logger->add('offers', $this->productsFromYml);
	}

	// собираем продукты, которые нужно отредактировать.
	private function collectProductsToEdit(): void
	{
		$this->logger->setGroup("----находим продукты, которые надо изменить");
		// 1. надо проверить артикул (артикул должен быть только номерной, кроме 777)
		// 2. надо проверить название (если в названии есть приписка с " - букетик", то этот суффикс надо обрезать)
		$productsToEditFromCrm = [];
		foreach ($this->productsFromCrm as $productFromCrm) {
			foreach ($this->productsFromYml as $productFromYml) {
				$externalIdFromYml = isset($productFromYml['groupId']) ? $productFromYml['groupId'] : $productFromYml['id'];
				if ($externalIdFromYml != $productFromCrm->externalId) continue;
				$name = $this->checkName($productFromYml['name']);
				$article = $this->checkArticle($productFromYml['vendorCode']);
				if ((!$name || $name == $productFromCrm->name) && (!$article || $article == $productFromCrm->article)) continue;
				$args = [
					'id' => $productFromCrm->id
				];
				if ($name) $args['name'] = $name;
				if ($article) $args['article'] = $article;
				$this->productsToEdit[] = $args;
				$productsToEditFromCrm[] = $productFromCrm;
				break;
			}
		}
		$this->logger->add('from_crm', $productsToEditFromCrm);
		$this->logger->add('from_yml', $this->productsToEdit);
	}

	// редактируем продукты.
	private function editProducts(): void
	{
		if (empty($this->productsToEdit)) return;

		// разбиваем массив на массивы по 50, потому что store_products_batch_edit не принимает больше 50 товаров
		$maxAmountProductsToAccept = 50;
		$chunks = array_chunk($this->productsToEdit, $maxAmountProductsToAccept);
		$i = 1;
		foreach ($chunks as $chunk) {
			$this->logger->setGroup("----изменяем продукты. порция $i");
			try {
				$this->logger->add('products_to_edit', $chunk);
				$this->retailcrm->products()->batchEdit($chunk);
			} catch (\Exception $e) {
				//отлавливаем исключение и ничего не делаем, так как логгирование уже осуществлено в момент выброса
			}
			$i++;
		}
	}

	// проверяет, надо ли нормализовывать артикул
	// нормализует артикул, если надо
	private function checkArticle($article)
	{
		$articleArray = explode('-', $article);
		if (count($articleArray) < 2) return null;
		if (in_array($articleArray[0], RESERVED_ARTIKULS)) return null;
		return $articleArray[0];
	}

	// проверяет, надо ли нормализовывать название
	// нормализует название, если надо
	private function checkName($name)
	{
		$pattern = '/\s-\s[^\p{Lu}]+[^-]*$/u';
		if (preg_match($pattern, $name)) {
			return preg_replace($pattern, '', $name);
		} else {
			return null;
		}
	}
}
