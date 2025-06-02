<?php

namespace php2steblya\helpers;

class Ensure
{
	public static function json(mixed $data): string
	{
		if (is_string($data)) $json = json_decode($data);
		if (is_array($data) || is_object($data)) $json = json_encode($data);
		if (json_last_error() === JSON_ERROR_NONE) return $json;
		throw new \Exception('не удалось преобразовать данные в JSON');
	}

	public static function array(mixed $data): array
	{
		if (is_array($data)) return $data;
		if (is_object($data)) return (array) $data;
		if (is_string($data)) {
			$decoded = json_decode($data, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
		}
		return ['data' => $data];
	}

	public static function object(mixed $data): object
	{
		if (is_object($data)) return $data;
		return (object) self::array($data);
	}

	public static function string(mixed $data): string
	{
		if (is_string($data)) return $data;
		if (is_scalar($data)) return strval($data);
		if (is_object($data) || is_array($data)) {
			$json = json_encode($data);
			if (json_last_error() === JSON_ERROR_NONE) return $json;
		}
		return '';
	}

	public static function number(mixed $data): float|int
	{
		if (is_int($data) || is_float($data)) return $data;
		if (is_bool($data)) return $data ? 1 : 0;
		if (is_null($data)) return 0;
		if (is_string($data)) {
			// Заменяем запятую на точку (русская десятичная)
			$data = str_replace(',', '.', $data);
			// Ищем первое похожее на число в строке
			if (preg_match('/[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/', $data, $matches)) {
				return strpos($matches[0], '.') !== false ? (float) $matches[0] : (int) $matches[0];
			}
		}
		throw new \Exception('не удалось привести значение к числу');
	}
}
