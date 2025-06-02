<?php

namespace php2steblya\helpers;

use php2steblya\helpers\Ensure;

class Arr
{
	// проверяет, является ли массив ассоциативным
	public static function isAssoc(array $array): bool
	{
		foreach (array_keys($array) as $key) {
			if (is_string($key)) return true;
		}
		return false;
	}

	// Удаляет ключ из массива и возвращает его значение.
	public static function pull(array &$array, string $key, $default = null)
	{
		if (!Ensure::array($array)) throw new \Exception('аргумент должен быть массивом. передан' . gettype($array));
		$value = $array[$key] ?? $default;
		unset($array[$key]);
		return $value;
	}
}
