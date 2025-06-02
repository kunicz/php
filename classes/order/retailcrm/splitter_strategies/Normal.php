<?php

namespace php2steblya\order\retailcrm\splitter_strategies;

use php2steblya\order\retailcrm\splitter_strategies\abstract_strategy as SplitterStrategy;
use php2steblya\Script;

class Normal extends SplitterStrategy
{
	private array $normals;
	private array $dopniks;

	public function __construct(array $od, array &$products, Script $script)
	{
		parent::__construct($od, $products, $script);
		$this->normals = array_filter($products, fn($product) => $product['isNormal']);
		$this->dopniks = array_filter($products, fn($product) => $product['isDopnik']);
	}

	public function split(): void
	{
		//$this->single_crm_od();
		$this->multiple_crm_ods();
	}

	public function needToSplit(): bool
	{
		return count($this->normals) > 0 || count($this->dopniks) === count($this->products);
	}

	// ВАРИАНТ СТРАТЕГИИ: нормальные товары и допники в одном заказе для CRM
	private function single_crm_od()
	{
		$this->logger->add('type', 'single_order');
		$this->resetOd();
		foreach ($this->products as $key => $product) {
			if (!$product['isNormal'] && !$product['isDopnik']) continue;

			$this->addProductToOd($product);
			$this->removeProductByKey($key);
		}
		$this->addOdToCrmOds();
	}

	// ВАРИАНТ СТРАТЕГИИ: каждый нормальный товаро в отдельном заказе для CRM
	// все допники - в первом заказе для CRM
	private function multiple_crm_ods()
	{
		$this->logger->add('type', 'multiple_orders');
		if (count($this->normals)) {
			$this->multiple_normalsAndDopniks();
		} else {
			$this->multiple_onlyDopniks();
		}
	}

	// логика для стратегии MULTIPLE_CRM_ODS: если в заказе есть нормальные товары
	private function multiple_normalsAndDopniks()
	{
		// normals
		foreach ($this->products as $key => $product) {
			if (!$product['isNormal']) continue;

			$this->resetOd();
			$this->addProductToOd($product);
			$this->addOdToCrmOds();
			$this->removeProductByKey($key);
		}

		//dopniks
		foreach ($this->products as $key => $product) {
			if (!$product['isDopnik']) continue;

			$this->appendProductToFirstCrmOd($product);
			$this->removeProductByKey($key);
		}
	}

	// логика для стратегии MULTIPLE_CRM_ODS: если в заказе есть только допники
	private function multiple_onlyDopniks()
	{
		$this->single_crm_od();
	}
}
