<?php

namespace php2steblya\api\modules\moysklad;

use php2steblya\helpers\Validate;
use php2steblya\api\ApiModule;

class Orders extends ApiModule
{
	public function get(array $data = []): object
	{
		return $this->request('GET', 'entity/customerorder', $data);
	}

	public function getById(array $data): object
	{
		Validate::notEmpty($data['id'], 'не передан id заказа');
		return $this->request('GET', "entity/customerorder/{$data['id']}");
	}

	public function create(array $data): object
	{
		Validate::notEmpty($data, 'не переданы данные для создания заказа');
		return $this->request('POST', 'entity/customerorder', $data);
	}

	public function edit(array $data): object
	{
		Validate::notEmpty($data['id'], 'не передан id заказа');
		Validate::notEmpty($data['args'], 'не переданы данные для обновления заказа');
		return $this->request('PUT', "entity/customerorder/{$data['id']}", $data['args']);
	}
}
