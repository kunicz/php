<?php

namespace php2steblya\order\utils;

use php2steblya\order\OrderData_name;
use php2steblya\order\OrderData_phone;
use php2steblya\order\OrderData_product;
use php2steblya\order\OrderData_products;
use php2steblya\order\OrderData_telegram;
use php2steblya\order\OrderData_dostavka;
use php2steblya\Script;

class OrderData_enricher
{
	public static function execute(array $od, Script $script): array
	{
		$od = self::dateTime($od);
		$od = self::zakazchik($od, $script);
		$od = self::dostavka($od);
		$od = self::payment($od);
		$od = self::products($od, $script);
		$od = self::shop($od, $script);
		return $od;
	}

	// дата и время
	private static function dateTime(array $od): array
	{
		$od['date'] = date('Y-m-d');
		$od['time'] = date('H:i:s');
		$od['datetime'] = $od['date'] . ' ' . $od['time'];

		return $od;
	}

	// заказчик и получатель
	private static function zakazchik(array $od, Script $script): array
	{
		$nameParts = OrderData_name::explode($od['name_zakazchika'] ?? 'customer_' . uniqid());
		$od['name_zakazchika_firstName'] = $nameParts[0];
		$od['name_zakazchika_lastName'] = $nameParts[1];
		$od['name_zakazchika_patronymic'] = $nameParts[2];
		$od['messenger_zakazchika'] = OrderData_telegram::get($od['messenger_zakazchika'] ?? '');
		$od['phone_zakazchika'] = OrderData_phone::normalize($od['phone_zakazchika'] ?? '');
		$od['phone_poluchatelya'] = OrderData_phone::normalize($od['phone_poluchatelya'] ?? '');
		$od['zakazchik_is_poluchatel'] = $od['phone_zakazchika'] == $od['phone_poluchatelya'];
		$od['customer_crm_id'] = self::customerCrmId($od['phone_zakazchika'], $script);

		return $od;
	}

	// получаем crmId заказчика
	private static function customerCrmId(string $phone, Script $script): ?int
	{
		$response  = $script->retailcrm->customers()->get(['filter' => ['name' => $phone]]);
		if (empty($response->customers)) return null;
		return $response->customers[0]->id;
	}

	// доставка
	private static function dostavka(array $od): array
	{
		$od['dostavka_interval'] = OrderData_dostavka::getInterval($od['dostavka_interval'] ?? $od['dostavka_interval_specialDates'] ?? '');
		$od['dostavka_price_initial'] = DOSTAVKA_PRICE;
		$od['dostavka_price_additional'] = OrderData_dostavka::getAdditionalPrice($od);
		$od['dostavka_price'] = $od['dostavka_price_initial'] + $od['dostavka_price_additional'];

		return $od;
	}

	// финансы и оплата
	private static function payment(array $od): array
	{
		$od['payment']['recieved'] = isset($od['payment']['systranid']);
		$od['payment']['income'] = $od['payment']['amount'];

		return $od;
	}

	// товары
	private static function products(array $od, Script $script): array
	{
		if (empty($od['payment']['products'])) return $od;

		foreach ($od['payment']['products'] as $key => $product) {
			$product['shop_crm_id'] = $od['shop_crm_id'];
			$od['payment']['products'][$key] = OrderData_product::prepare($product, $script);
		}

		// добавляет бесплатные очки для GVOZDISCO
		if ($od['shop_crm_id'] == GVOZDISCO_CRM_ID) {
			$od['payment']['products'] = OrderData_products::o4kiGvozdisco($od['payment']['products']);
		}

		// сортируем товары (сперва каталожные сборные, потом все осальные)
		$od['payment']['products'] = OrderData_products::sort($od['payment']['products']);

		return $od;
	}

	//магазин
	private static function shop(array $od, Script $script): array
	{
		$shop = array_filter($script->shops, fn($shop) => $shop['shop_crm_id'] == $od['shop_crm_id']);
		$od['shop'] = reset($shop);
		return $od;
	}
}
