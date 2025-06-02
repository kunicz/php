<?php

namespace php2steblya\order\utils;

class OrderData_normalizer
{
	public static function execute(array $od): array
	{
		$od = self::underscoreKeys($od);
		$od = self::booleanYes($od);
		$od = self::sanitizeInputs($od);
		return $od;
	}

	// заменяем все тире на нижнее подчеркивание
	private static function underscoreKeys(array $orderData): array
	{
		$od = [];
		foreach ($orderData as $key => $value) {
			if (strpos($key, '-') === false) {
				$od[$key] = $value;
				continue;
			}
			$od[str_replace('-', '_', $key)] = $value;
		}
		return $od;
	}

	// заменяем все yes на true
	private static function booleanYes(array $orderData): array
	{
		$od = [];
		foreach ($orderData as $key => $value) {
			$od[$key] = $value === 'yes' ? true : $value;
		}
		return $od;
	}

	// санитизируем поля
	private static function sanitizeInputs(array $orderData): array
	{
		$od = $orderData;
		$fieldsToSanitize = [
			'name_zakazchika',
			'name_poluchatelya',
			'messenger_zakazchika',
			'comment_courier',
			'comment_florist',
			'text_v_kartochku',
			'adres_poluchatelya_city',
			'adres_poluchatelya_street',
			'adres_poluchatelya_dom',
			'adres_poluchatelya_korpus',
			'adres_poluchatelya_stroenie',
			'adres_poluchatelya_kvartira',
			'adres_poluchatelya_etazh',
			'adres_poluchatelya_podezd',
			'adres_poluchatelya_domofon'
		];
		foreach ($orderData as $key => $value) {
			if (!in_array($key, $fieldsToSanitize)) continue;

			// убираем невидимые control-символы (например, из Word, WhatsApp, email)
			$value = preg_replace('/[^\P{C}]+/u', '', $value);
			// убираем пробелы в начале и конце строки
			$value = trim($value);

			$od[$key] = $value;
		}
		return $od;
	}
}
