<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\api\ApiModule;

class Couriers extends ApiModule
{
	public function get($data = []): object
	{
		return $this->request('GET', 'reference/couriers', $data);
	}
}
