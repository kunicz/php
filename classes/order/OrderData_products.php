<?php

namespace php2steblya\order;

use php2steblya\order\OrderData_product;

class OrderData_products
{
	// сортирует товары по принципу:
	// 1. букеты
	// 2. все остальные
	public static function sort($products)
	{
		if (count($products) === 1) return $products;

		$catalog = [];
		$other = [];
		foreach ($products as $product) {
			if ($product['isDonat'] || $product['isDopnik']) {
				$other[] = $product;
			} else {
				$catalog[] = $product;
			}
		}
		return array_merge($catalog, $other);
	}

	// выебри карточку
	public static function cards($products)
	{
		$items = [];
		foreach ($products as $product) {
			if (!isset($product['options'])) continue;

			$cardOption = OrderData_product::findOption($product['options'], OPTION_CARD);
			if ($cardOption && isset($cardOption['variant'])) {
				$items[] = $cardOption['variant'];
			}
		}
		return implode(', ', $items);
	}

	// букеты в заказе
	public static function bukety($products)
	{
		$items = [];
		foreach ($products as $product) {
			$item = '';
			//название
			$item .= $product['name'];
			if (isset($product['options'])) {
				//формат
				$formatOption = OrderData_product::findOption($product['options'], OPTION_FORMAT);
				if ($formatOption) $item .= ' - ' . $formatOption['variant'];

				//свойства
				$filteredOptions = OrderData_product::filterOptionsNotInList($product['options'], [OPTION_CARD, OPTION_FORMAT]);
				$dops = [];
				foreach ($filteredOptions as $option) {
					$dops[] = $option['option'] . ': ' . $option['variant'];
				}
				if (!empty($dops)) $item .= ' (' . implode(', ', $dops) . ')';
			}
			//количество
			$item .= ' (' . $product['quantity'] . ' шт)';

			$items[] = $item;
		}
		return implode(', ', $items);
	}

	// очки GVOZDISCO
	public static function o4kiGvozdisco($products)
	{
		foreach ($products as &$product) {
			if ($product['artikul'] == ARTIKUL_DOPNIK . '-o4kiFree') {
				$product['quantity'] = 1;
				$product['name'] .= ' (бесплатные)';
			}
		}
		return $products;
	}
}
