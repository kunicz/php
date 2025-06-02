<?php

namespace php2steblya\order;

class OrderData_dostavka
{
	public static function getInterval(string $str): string
	{
		// c 10:00 до 12:00
		if (preg_match('/^с \d{2}:\d{2} до \d{2}:\d{2}/', $str, $matches)) return $matches[0];
		// к точному времени (+600 р.) и подобные
		if (strpos($str, '(') !== false) return preg_replace('/\(.+$/', '', $str);
		return $str;
	}

	public static function getAdditionalPrice(array $od): int
	{
		$price = 0;
		$pattern = '/=\s(\d+)$/';
		// interval
		if (isset($od['dostavka_interval']) && preg_match($pattern, $od['dostavka_interval'], $matches)) $price += (int) $matches[1];
		// zone
		if (isset($od['dostavka_zone']) && preg_match($pattern, $od['dostavka_zone'], $matches)) $price += (int) $matches[1];
		return $price;
	}
}
