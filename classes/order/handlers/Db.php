<?php

namespace php2steblya\order\handlers;

use php2steblya\order\handlers\Abstract_handler as OrderHandler;

// Сохраняет данные заказа в БД
// сохраняются как оплаченные, так и неоплаченные заказы
class Db extends OrderHandler
{
	public function execute()
	{
		$this->validateData();
		$args = [
			'set' => [
				'shop_crm_id' => $this->od['shop_crm_id'],
				'paid' => $this->od['payment']['recieved'],
				'order_data' => json_encode($this->od),
				'product_name' => $this->getProductsNames(),
				'customer_name' => $this->od['name_zakazchika'],
				'tilda_order_id' => $this->od['payment']['orderid']
			]
		];
		$this->logger->add('args', $args);
		$this->script->db->orders()->insert($args);
	}

	// Проверка входных данных
	private function validateData()
	{
		if (!isset($this->od['payment']['recieved'])) throw new \Exception('в od отсутствует поле payment.recieved');
		if (empty($this->od['payment']['orderid'])) throw new \Exception('в od отсутствует поле payment.orderid');
		if (empty($this->od['name_zakazchika'])) throw new \Exception('в od отсутствует поле name_zakazchika');
		if (empty($this->od['shop_crm_id'])) throw new \Exception('в od отсутствует поле shop_crm_id');
	}

	// Получает имена товаров через запятую
	private function getProductsNames(): string
	{
		return isset($this->od['payment']['products'])
			? implode(', ', array_column($this->od['payment']['products'], 'name'))
			: '';
	}
}
