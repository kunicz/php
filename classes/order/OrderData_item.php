<?php

namespace php2steblya\order;

use php2steblya\db\Db;
use php2steblya\Logger;

class OrderData_item
{
	// получает externalId из каталога для передачи в crm
	public static function getOffer($product)
	{
		try {
			if (empty($product['shop_crm_id'])) throw new \Exception("shop_crm_id не указан");

			$db = Db::createService();
			$logger = Logger::getInstance();

			$logger->setSubGroup('yml_catalog_db');
			$dbResponse = $db->tilda_yml_catalog()->get([
				'fields' => ['catalog'],
				'where' => ['shop_crm_id' => $product['shop_crm_id']],
				'limit' => 1,
			]);
			$logger->exitSubGroup();

			if (empty($dbResponse)) throw new \Exception("каталог для {$product['shop_crm_id']}) не найден в бд");

			$catalog = json_decode($dbResponse, true);

			foreach ($catalog['offers'] as $catalogOffer) {
				if ($catalogOffer['vendorCode'] != $product['sku']) continue;
				return ['externalId' => $catalogOffer['id']];
			}

			throw new \Exception("sku ({$product['sku']}) не найден в каталоге");
		} catch (\Exception $e) {
			$logger->addError($e);
			return [];
		}
	}

	public static function getProperties($product)
	{
		$props = [];

		// опции
		if (isset($product['options'])) {
			foreach ($product['options'] as $prop) {
				$name = htmlspecialchars_decode($prop['option']); //&amp; -> &
				$name = str_replace('?', '', $name); //удаляем ? в названиях опций (скока? какой цвет?)
				$value = preg_replace('/\s*\([^)]+\)/', '', $prop['variant']); //удаляем все, что в скобках
				if (!$value) continue;
				$props[] = [
					'name' => $name,
					'value' => $value
				];
			}
		}
		// артикул
		$props[] = [
			'name' => 'артикул',
			'value' => $product['artikul']
		];
		// sku
		$props[] = [
			'name' => 'sku',
			'value' => $product['sku']
		];
		// витрина
		if ($product['isVitrina']) {
			$props[] = [
				'name' => 'готов',
				'value' => 'на витрине'
			];
		}
		// цена
		$props[] = [
			'name' => 'цена',
			'value' => $product['price']
		];

		return $props;
	}
}
