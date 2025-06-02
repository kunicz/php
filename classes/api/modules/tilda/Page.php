<?php

namespace php2steblya\api\modules\tilda;

use php2steblya\api\ApiModule;

/**
 * Модуль для работы со страницами тильды.
 */
class Page extends ApiModule
{
	public function get(array $data): object
	{
		return $this->request('GET', 'getpageexport', $data);
	}
}
