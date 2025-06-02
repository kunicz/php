<?php

namespace php2steblya\order\retailcrm;

use php2steblya\order\OrderData_adres;
use php2steblya\order\OrderData_item;
use php2steblya\order\OrderData_products;
use php2steblya\Script;

class Mapper
{
	public static function execute(array $ods, Script $script): array
	{
		return self::mapOds($ods);
	}

	public static function mapOds(array $ods): array
	{
		$orders = [];
		foreach ($ods as $od) {
			$orders[] = self::mapOd($od);
		}
		return $orders;
	}

	public static function mapOd(array $od): array
	{
		$uniqid = uniqid();
		$order = [
			'externalId' => 'php_' . time() . $uniqid,
			'orderMethod' => 'php',
			'site' => $od['shop_crm_code'] ?? null,
			'managerId' => $od['manager_id'] ?? null,
			'firstName' => $od['name_zakazchika_firstName'] ?? '',
			'lastName' => $od['name_zakazchika_lastName'] ?? '',
			'patronymic' => $od['name_zakazchika_patronymic'] ?? '',
			'phone' => $od['phone_zakazchika'] ?? '',
			'email' => $od['email_zakazchika'] ?? '',
			'customerComment' => $od['comment_courier'] ?? '',
			'managerComment' => $od['comment_florist'] ?? '',
			'delivery' => [
				'code' => 'courier',
				'address' => ['text' => OrderData_adres::getText($od)],
				'date' => $od['dostavka_date'] ?? '',
				'time' => ['custom' => $od['dostavka_interval'] ?? ''],
				'cost' => $od['dostavka_price'] ?? DOSTAVKA_PRICE, // стоимость доставки для клиента
				'netCost' => 0 // стоимость доставки для курьера (обнулил, чтоб девки всегда руками вводили)
			],
			'customFields' => [
				'onanim' => $od['onanim'] ?? false,
				'lovixlube' => $od['lovixlube'] ?? false,
				'ya_client_id_order' => $od['ya_client_id'] ?? '',
				'stoimost_dostavki_iz_tildy' => $od['dostavka_price'] ?? DOSTAVKA_PRICE,
				'text_v_kartochku' => $od['text_v_kartochku'] ?? '',
				'name_poluchatelya' => $od['name_poluchatelya'] ?? '',
				'phone_poluchatelya' => $od['phone_poluchatelya'] ?? '',
				'otkuda_o_nas_uznal' => $od['otkuda_uznal_o_nas'] ?? '',
				'messenger_zakazchika' => $od['messenger_zakazchika'] ?? '',
				'zakazchil_poluchatel' => $od['zakazchik_is_poluchatel'] ?? false, // опечатку не исправить
				'uznat_adres_u_poluchatelya' => $od['uznat_adres_u_poluchatelya'] ?? false,
				'domofon' => $od['adres_poluchatelya_domofon'] ?? '',
				'adres_poluchatelya' => OrderData_adres::getText($od),
				'bukety_v_zakaze' => OrderData_products::bukety($od['payment']['products']),
				'card' => OrderData_products::cards($od['payment']['products'])
			],
			'source' => [
				'keyword' => $od['utm_term'] ?? '',
				'source' => $od['utm_source'] ?? '',
				'medium' => $od['utm_medium'] ?? '',
				'content' => $od['utm_content'] ?? '',
				'campaign' => $od['utm_campaign'] ?? ''
			],
			'items' => []
		];

		// customer_crm_id
		if (!empty($od['customer_crm_id'])) {
			$order['customer']['id'] = $od['customer_crm_id'];
		}

		// payment
		// статус заказа указывается тоже именно здесь, так как зависит от статуса оплаты (да/нет)
		$order['status'] = $od['payment']['recieved'] ? 'new' : 'not-paid';
		$order['payments'] = [
			[
				'type' => 'site',
				'paidAt' => $od['datetime'],
				'amount' => $od['payment']['amount'],
				'status' => $od['payment']['recieved'] ? 'paid' : 'not-paid',
				'externalId' => $uniqid . ($od['payment']['systranid'] ?? ''),
				'comment' => $od['payment']['recieved'] ? self::paymentComment($od) : ''
			]
		];

		// products
		foreach ($od['payment']['products'] as $product) {
			$item =  [
				'productName' => $product['name'],
				'quantity' => $product['quantity'],
				'initialPrice' => $product['price'],
				'purchasePrice' => $product['purchasePrice']
			];
			$offer = OrderData_item::getOffer($product);
			if (!empty($offer)) {
				$item['offer'] = $offer;
			}
			$properties = OrderData_item::getProperties($product);
			if (!empty($properties)) {
				$item['properties'] = $properties;
			}

			$order['items'][] = $item;
		}

		// donat
		// устанавливаем стаутс и флориста
		if ($od['payment']['products'][0]['isDonat']) {
			$order['status'] = 'complete';
			$order['customFields']['florist'] = 'boss';
		}

		return $order;
	}

	private static function paymentComment(array $od): string
	{
		if (isset($od['payment']['promocode'])) {
			$promocode = $od['payment']['promocode'];
			$discount = $od['payment']['discount'];
			return 'применен промокод: "' . $promocode . '" (' . $discount . ' р.)';
		}
		return '';
	}
}
