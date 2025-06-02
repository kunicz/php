<?php

namespace php2steblya\order;

use php2steblya\order\OrderData_adres;

class OrderData_telegram
{
	public static function get($telegram = '')
	{
		$telegram = str_replace('https://t.me/', '', $telegram);
		$telegram = str_replace('@', '', $telegram);
		$telegram = strtolower($telegram);
		return preg_match('/^[a-z0-9_.-]+$/', $telegram) ? $telegram : '';
	}

	public static function getMessageForChannel($od)
	{
		$message = [];

		$message = array_merge($message, self::products($od));
		$message = array_merge($message, self::shop($od));
		$message = array_merge($message, self::lovix($od));
		$message = array_merge($message, self::zakazchik($od));
		$message = array_merge($message, self::poluchatel($od));
		$message = array_merge($message, self::dostavka($od));
		$message = array_merge($message, self::comments($od));
		$message = array_merge($message, self::summary($od));
		$message = array_merge($message, self::utm($od));
		$message = array_merge($message, self::promocode($od));

		return implode("\n", $message);
	}

	private static function products($od)
	{
		$message = [];
		$products = [];

		foreach ($od['payment']['products'] as $product) {
			$products[] = self::formatProductString($product);
		}

		$message[] = '🌸 *Товары*: ' . self::sanitize(implode(', ', $products));
		return $message;
	}

	private static function formatProductString($product)
	{
		$props = self::getProductProperties($product);
		$name = $product['name'];
		$vitrina = $product['isVitrina'] ? ' с витрины' : '';
		$properties = !empty($props) ? implode(' ', $props) . ', ' : '';
		$quantity = $product['quantity'] . ' шт';
		$price = $product['amount'] . ' р.';

		return "{$name}{$vitrina} ({$properties}{$quantity}) - {$price}";
	}

	private static function getProductProperties($product)
	{
		$props = [];

		if (!isset($product['options'])) return $props;

		foreach ($product['options'] as $option) {
			switch ($option['option']) {
				case OPTION_FORMAT:
					$props[0] = $option['variant'];
					break;
				case OPTION_CARD:
					$props[1] = $option['variant'];
					break;
			}
		}

		return $props;
	}

	private static function shop($od)
	{
		return ['🏪 *Магазин*: ' . self::sanitize($od['shop']['shop_title'])];
	}

	private static function lovix($od)
	{
		return !empty($od['lovixlube']) ? ['❤️ Добавить Lovix'] : [];
	}

	private static function zakazchik($od)
	{
		$message = [];

		// Заказчик
		$message[] = '🙎‍♂️ *Заказчик*:';
		$message[] = self::sanitize($od['name_zakazchika']) .
			(!empty($od['messenger_zakazchika']) ? ' \(@' . self::sanitize($od['messenger_zakazchika']) . '\)' : '');
		$message[] = self::sanitize($od['phone_zakazchika']);
		if (!empty($od['onanim'])) $message[] = '_\(аноним\)_';

		return $message;
	}

	private static function poluchatel($od)
	{
		$message = [];
		if (empty($od['name_poluchatelya']) && empty($od['phone_poluchatelya'])) return $message;

		$message[] = '🙎 *Получатель*:';
		if (!empty($od['name_poluchatelya'])) $message[] = self::sanitize($od['name_poluchatelya']);
		if (!empty($od['phone_poluchatelya'])) $message[] = self::sanitize($od['phone_poluchatelya']);

		return $message;
	}

	private static function dostavka($od)
	{
		$message = [];

		$hasPhysicalProducts = false;
		foreach ($od['payment']['products'] as $product) {
			if ($product['isDonat']) continue;
			$hasPhysicalProducts = true;
			break;
		}
		if (!$hasPhysicalProducts) return $message;

		$message[] = '🏠 *Доставка*:';
		if (!empty($od['uznat_adres_u_poluchatelya'])) {
			$message[] = 'узнать адрес у получателя';
		} else {
			if (OrderData_adres::getText($od)) $message[] = self::sanitize(OrderData_adres::getText($od));
		}
		$message[] = self::sanitize(date('d.m.Y', strtotime($od['dostavka_date'])) . ' ' . $od['dostavka_interval']);
		return $message;
	}

	private static function comments($od)
	{
		$message = [];

		if (!empty($od['comment_florist'])) {
			$message[] = '💬 *Комментарий флористу*:';
			$message[] = self::sanitize($od['comment_florist']);
		}
		if (!empty($od['comment_courier'])) {
			$message[] = '💬 *Комментарий курьеру*:';
			$message[] = self::sanitize($od['comment_courier']);
		}
		if (!empty($od['text_v_kartochku'])) {
			$message[] = '💬 *Текст в карточку*:';
			$message[] = self::sanitize($od['text_v_kartochku']);
		}

		return $message;
	}

	private static function summary($od)
	{
		$message = [];

		$message[] = '💵 *Сумма заказа*: ' . $od['payment']['amount'] . ' р\.';
		$message[] = '⏱ *Время заказа*: ' . self::sanitize(date('d.m.Y H:i', strtotime($od['datetime'])));
		$message[] = $od['payment']['recieved'] ? '✅ Оплачен' : '⛔️ Не оплачен';

		return $message;
	}

	private static function utm($od)
	{
		$message = [];

		$utm = [
			'keyword'   => $od['utm_term'] ?? null,
			'source'    => $od['utm_source'] ?? null,
			'medium'    => $od['utm_medium'] ?? null,
			'content'   => $od['utm_content'] ?? null,
			'campaign'  => $od['utm_campaign'] ?? null
		];
		foreach ($utm as $key => $value) {
			if ($value) $message[] = '*utm\-' . self::sanitize($key) . '*: ' . self::sanitize($value);
		}

		return $message;
	}

	private static function promocode($od)
	{
		$message = [];

		if (empty($od['payment']['promocode'])) return $message;
		$messagw[] = '🛍 *Промокод*: "' . self::sanitize($od['payment']['promocode']) . '" \(' . self::sanitize($od['payment']['discount']) . ' р\.\)';

		return $message;
	}

	private static function sanitize($text)
	{
		$escapeChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
		$sanitized = str_replace($escapeChars, array_map(fn($char) => '\\' . $char, $escapeChars), $text);

		return $sanitized;
	}
}
