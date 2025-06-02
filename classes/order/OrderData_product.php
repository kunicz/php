<?php

namespace php2steblya\order;

use php2steblya\Script;

class OrderData_product
{
	// Подготавливает данные продукта
	public static function prepare(array $product, Script $script)
	{
		$product['name'] = self::name($product);
		$product['sku'] = self::sku($product);
		$product['artikul'] = self::artikul($product);
		$product['isVitrina'] = self::isVitrina($product);
		$product['isDonat'] = (int) $product['sku'] == ARTIKUL_DONAT;
		$product['isDopnik'] = str_starts_with($product['sku'], ARTIKUL_DOPNIK);
		$product['isPodpiska'] = str_starts_with($product['sku'], ARTIKUL_PODPISKA);
		$product['isNormal'] = !$product['isDonat'] && !$product['isDopnik'] && !$product['isPodpiska'];
		$product['price'] = (int) $product['price'];
		$product['purchasePrice'] = (int) self::purchasePrice($product, $script);
		$product['quantity'] = (int) $product['quantity'];
		$product['amount'] = (int) $product['amount'];
		$product = self::updateOption($product, 'карточка', OPTION_CARD);
		$product = self::updateOption($product, 'формат', OPTION_FORMAT);

		return $product;
	}

	// Название продукта
	private static function name(array $product): string
	{
		$name = html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8');
		if ($name === 'ЛЮБЛЮЮЮ...') {
			$loveOption = self::findOption($product['options'] ?? [], 'love is');
			$name .= $loveOption['variant'] ?? '';
		}
		return $name;
	}

	// Sku продукта (123-10)
	private static function sku(array $product): string
	{
		// не стоит путаться. То, что тильда передает как $product['sku']
		// в моей терминологии является именно артикулом, а не ску
		$sku = $product['sku'];

		// обрезаем 'v' для каталожных витринных товароы (123-10v)
		if (substr($sku, -1) == 'v') $sku = substr($sku, 0, -1);

		return $sku;
	}

	// Артикул продукта (123)
	private static function artikul(array $product): string
	{
		$articleArray = explode('-', $product['sku']);
		if (count($articleArray) < 2) return $product['sku'];
		return $articleArray[0];
	}

	// Является ли продукт витриной
	private static function isVitrina(array $product)
	{
		if (str_starts_with($product['sku'], ARTIKUL_VITRINA)) return true;
		return substr($product['sku'], -1) == 'v';
	}

	// закупочная стоимость товара
	private static function purchasePrice(array $product, Script $script): int
	{
		if (!$product['isDopnik']) return 0;

		$args = [
			'fields' => ['purchase_price'],
			'where' => ['shop_crm_id' => $product['shop_crm_id'], 'title' => $product['name']],
			'limit' => 1
		];
		$response = $script->db->products()->get($args);

		return empty($response) ? 0 : $response;
	}

	// Находит опцию по названию в массиве опций
	public static function findOption(?array $options, string $optionName): ?array
	{
		if (empty($options)) return null;

		foreach ($options as $option) {
			if (!isset($option['option'])) continue;
			if ($option['option'] === $optionName) return $option;
		}

		return null;
	}

	// Обновляет опцию продукта
	public static function updateOption(array $product, string $oldOptionName, string $newOptionName): array
	{
		if (!isset($product['options'])) return $product;

		$option = self::findOption($product['options'], $oldOptionName);
		if ($option) {
			$index = array_search($option, $product['options']);
			$product['options'][$index]['option'] = $newOptionName;
		}

		return $product;
	}

	// Фильтрует опции продукта по списку исключений
	public static function filterOptionsNotInList(?array $options, array $excludeOptions): array
	{
		if (empty($options)) return [];

		$filteredOptions = [];
		foreach ($options as $option) {
			if (!isset($option['option'])) continue;
			if (in_array($option['option'], $excludeOptions)) continue;
			$filteredOptions[] = $option;
		}

		return $filteredOptions;
	}
}
