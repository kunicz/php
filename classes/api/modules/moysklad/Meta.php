<?php

namespace php2steblya\api\modules\moysklad;

use php2steblya\api\ApiModule;

/**
 * Модуль для работы с заказами в API МойСклад.
 */
class Meta extends ApiModule
{
	public function get(array $data = []): object
	{
		return $this->request('GET', 'entity/metadata', $data);
	}
}
