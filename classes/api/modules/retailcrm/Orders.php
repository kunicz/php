<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\helpers\Validate;
use php2steblya\api\ApiModule;

class Orders extends ApiModule
{
	public function get(array $data = []): object
	{
		$data['limit'] = $data['limit'] ?? 100;
		return $this->request('GET', 'orders', $data);
	}

	public function getById(array $data = []): object
	{
		Validate::notEmpty($data['id'], 'не передан id заказа');
		Validate::notEmpty($data['site'], 'не передан site');
		return $this->request('GET', "orders/{$data['id']}", ['by' => 'id', 'site' => $data['site']]);
	}

	public function edit(array $data = []): object
	{
		Validate::notEmpty($data['id'], 'не передан id заказа');
		Validate::notEmpty($data['args'], 'не переданы данные для обновления заказа');
		return $this->request('POST', "orders/{$data['id']}/edit", $data['args']);
	}

	public function create(array $data = []): object
	{
		Validate::notEmpty($data, 'не переданы данные для создания заказа');
		return $this->request('POST', 'orders/create', $data);
	}
}
