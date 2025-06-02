<?php

namespace php2steblya\order\retailcrm\splitter_strategies;

use php2steblya\Logger;
use php2steblya\Script;

// абстрактый класс-директива для стратегий разделения заказа из тильды на заказы в CRM
abstract class Abstract_strategy
{
	protected array $initial_od; // оригинальные данные заказа из тильды
	protected array $od; // текущая версия заказа, которую надо мутировать
	protected array $products; // остаток из необработанных товаров (вычерпывается стратегиями)
	protected array $crm_ods = []; // массив заказов для CRM, который будет возвращен в Splitter
	protected array $processedProducts = []; // массив обработанных данной стратегией товаров
	protected Script $script;
	protected Logger $logger;

	public function __construct(array $od, array &$products, Script $script)
	{
		$this->initial_od = $od;
		$this->products = &$products;
		$this->script = $script;
		$this->logger = $script->logger;
		$this->resetOd();
	}

	// проверка и выполнение стратегии
	public function execute(): array
	{
		if (!$this->needToSplit()) return [];

		$this->split();
		$this->logger->add('processed_products', $this->processedProducts);
		return $this->crm_ods;
	}

	// проверка на необходимость применения стратегии
	abstract public function needToSplit(): bool;

	// дробление, вычленение заказов из одного в несколько, если нужно
	abstract protected function split(): void;

	// удаление обработанного товара из списка
	protected function removeProductByKey(int $key): void
	{
		$this->processedProducts[] = $this->products[$key];
		unset($this->products[$key]);
	}

	// очищаем список товаров 
	protected function removeProductsFromOd()
	{
		$this->od['payment']['products'] = [];
	}

	// добавляем товар в список (до сплиттинга)
	protected function addProductToOd(array $product): void
	{
		$this->od['payment']['products'][] = $product;
	}

	// добавляет отрезанный и сформированный заказ в массив заказов для CRM
	// в зависимости от стратегии добавляем или нет доставку в заказе
	protected function addOdToCrmOds(): void
	{
		$this->crm_ods[] = $this->od;
	}

	// добавляем товар в первый заказ для CRM (после сплиттинга)
	protected function appendProductToFirstCrmOd(array $product): void
	{
		$this->crm_ods[0]['payment']['products'][] = $product;
	}

	// сбрасывает orderData к начальному состоянию
	// очищает список товаров
	protected function resetOd(): void
	{
		$this->od = $this->initial_od;
		$this->removeProductsFromOd();
	}

	// имя наследника класса стратегии (например, 'donat', 'normal' и т.д.)
	public function strategyName(): string
	{
		$classParts = explode('\\', get_class($this));
		return strtolower(end($classParts));
	}
}
