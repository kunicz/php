<?php

namespace php2steblya\order\retailcrm;

use php2steblya\Logger;
use php2steblya\Script;

class Calculator
{
	private static int $income;
	private static Logger $logger;

	public static function execute(array $orders, string $strategyName, int $income, Script $script): array
	{
		self::$income = $income;
		self::$logger = $script->logger;

		foreach ($orders as $key => &$od) {
			// ссылки на переменные
			$products = &$od['payment']['products'];
			$subtotal = &$od['payment']['subtotal'];
			$amount = &$od['payment']['amount'];
			$dostavka_initital = $od['dostavka_price']; // резервируем оригинальное значение
			$dostavka_recalculated = &$od['dostavka_price']; // значение, которое может быть изменено
			$ostatok = &$od['payment']['ostatok'];

			//subtotal
			$subtotal = array_reduce($products, function ($acc, $product) {
				return $acc + $product['price'] * $product['quantity'];
			}, 0);

			//dostavka
			switch ($strategyName) {
				// стратегии без доставки вообще
				case 'donat':
					$dostavka_initital = 0;
					$dostavka_recalculated = 0;
					break;

				// стратегии с доставкой только в одном заказе в стеке
				case 'normal':
					$dostavka_recalculated = $key === 0 ? $dostavka_initital : 0;
					break;

				// стратегии с доставкой в каждом заказе в стеке
				case 'podpiska':
				default:
					// доставка не обнуляется
			}

			//amount
			$amount = self::reduce($subtotal + $dostavka_initital);

			//ostatok
			$ostatok = self::$income;
		}

		return $orders;
	}

	// вычитание денег
	// вычерпывание остатка денег и возврат доступных денег
	private static function reduce(int $sum): int
	{
		if (self::$income >= $sum) {
			self::$income -= $sum;
			self::$logger->add('остаток', self::$income . ' р.');
			return $sum;
		} else {
			$ostatok = self::$income;
			self::$income = 0;
			self::$logger->add('не хватает', ($sum - $ostatok) . ' р.');
			return max($ostatok, 0);
		}
	}
}
