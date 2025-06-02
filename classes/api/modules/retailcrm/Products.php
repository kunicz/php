<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\Script;
use php2steblya\helpers\Validate;
use php2steblya\helpers\Ensure;
use php2steblya\api\ApiModule;

class Products extends ApiModule
{
	public function get(array $data = []): object
	{
		$data['limit'] = $data['limit'] ?? 100;
		return $this->request('GET', 'store/products', $data);
	}

	public function batchEdit(array $products): object
	{
		foreach ($products as $index => $product) {
			try {
				Validate::notEmpty($product['id'] ?? null, "у продукта с индексом $index отсутствует ID");
				Validate::notEmpty($product, "продукт с индексом $index имеет некорректный формат");
			} catch (\Exception $e) {
				unset($products[$index]);
				Script::notifyAdmin($e);
			}
		}

		Validate::notEmpty($products, 'не переданы данные для массового обновления продуктов');

		$data = ['products' => Ensure::json(array_values($products))];

		return $this->request('POST', 'store/products/batch/edit', $data);
	}
}
