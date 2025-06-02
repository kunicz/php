<?php

namespace php2steblya\order;

class OrderData_adres
{
	public static function getText($od)
	{
		$adres = [];
		if (!empty($od['adres_poluchatelya_region']))	$adres[] = $od['adres_poluchatelya_region'];
		if (!empty($od['adres_poluchatelya_city']))		$adres[] = 'г. ' . $od['adres_poluchatelya_city'];
		if (!empty($od['adres_poluchatelya_street']))	$adres[] = $od['adres_poluchatelya_street'];
		if (!empty($od['adres_poluchatelya_dom']))		$adres[] = 'д. ' . $od['adres_poluchatelya_dom'];
		if (!empty($od['adres_poluchatelya_korpus']))	$adres[] = 'корп. ' . $od['adres_poluchatelya_korpus'];
		if (!empty($od['adres_poluchatelya_stroenie']))	$adres[] = 'стр. ' . $od['adres_poluchatelya_stroenie'];
		if (!empty($od['adres_poluchatelya_kvartira']))	$adres[] = 'кв. ' . $od['adres_poluchatelya_kvartira'];
		if (!empty($od['adres_poluchatelya_podezd']))	$adres[] = 'подъезд ' . $od['adres_poluchatelya_podezd'];
		if (!empty($od['adres_poluchatelya_etazh']))	$adres[] = 'этаж ' . $od['adres_poluchatelya_etazh'];
		return implode(', ', $adres);
	}

	public static function getArray($od)
	{
		return [
			'region'	=> $od['adres_poluchatelya_region'] ?? '',
			'city'		=> $od['adres_poluchatelya_city'] ?? '',
			'street'	=> $od['adres_poluchatelya_street'] ?? '',
			'building'	=> $od['adres_poluchatelya_dom'] ?? '',
			'housing'	=> $od['adres_poluchatelya_korpus'] ?? '',
			'house'		=> $od['adres_poluchatelya_stroenie'] ?? '',
			'flat'		=> $od['adres_poluchatelya_kvartira'] ?? '',
			'floor'		=> $od['adres_poluchatelya_etazh'] ?? '',
			'block'		=> $od['adres_poluchatelya_podezd'] ?? '',
			'domofon'	=> $od['adres_poluchatelya_domofon'] ?? ''
		];
	}
}
