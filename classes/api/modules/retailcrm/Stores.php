<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\api\ApiModule;

class Stores extends ApiModule
{
	public function get($data = []): object
	{
		return $this->request('GET', 'reference/stores', $data);
	}

	public function getActive($data = []): object
	{
		$apiResponse = $this->get($data);
		$apiResponse->stores = array_values(array_filter(
			$apiResponse->stores,
			fn($store) => $store->active === true
		));
		return $apiResponse;
	}
}
