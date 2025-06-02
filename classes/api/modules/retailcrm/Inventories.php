<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\helpers\Ensure;
use php2steblya\helpers\Validate;
use php2steblya\api\ApiModule;

class Inventories extends ApiModule
{
	public function upload(array $data = []): object
	{
		Validate::notEmpty($data, 'не переданы данные для загрузки инвентаризации');
		if (!isset($data['offers'])) $data['offers'] = Ensure::json($data);
		return $this->request('POST', 'store/inventories/upload', $data);
	}
}
