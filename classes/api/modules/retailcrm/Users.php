<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\helpers\Validate;
use php2steblya\api\ApiModule;

class Users extends ApiModule
{
	public function get(array $data = []): object
	{
		$data['limit'] = $data['limit'] ?? 100;
		return $this->request('GET', 'users', $data);
	}

	public function getById(array $data = []): object
	{
		Validate::notEmpty($data['id'], 'не передан id заказа');
		return $this->request('GET', "users/{$data['id']}");
	}

	public function edit(array $data = []): object
	{
		Validate::notEmpty($data['id'], 'не передан id заказа');
		Validate::notEmpty($data['args'], 'не переданы данные для обновления заказа');
		return $this->request('POST', "users/{$data['id']}/edit", $data['args']);
	}

	public function create(array $data = []): object
	{
		Validate::notEmpty($data, 'не переданы данные для создания заказа');
		return $this->request('POST', 'users/create', $data);
	}
}
