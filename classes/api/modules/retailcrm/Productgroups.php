<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\helpers\Validate;
use php2steblya\api\ApiModule;

class ProductGroups extends ApiModule
{
	public function get(array $data = []): object
	{
		$data['limit'] = $data['limit'] ?? 100;
		return $this->request('GET', 'store/product-groups', $data);
	}

	public function edit(array $data = []): object
	{
		Validate::notEmpty($data['id'], 'не передан ID группы продуктов');
		Validate::notEmpty($data['args'], 'не переданы данные для обновления группы продуктов');
		return $this->request('POST', "store/product-groups/{$data['id']}/edit", $data['args']);
	}
}
