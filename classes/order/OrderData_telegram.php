<?

namespace php2steblya\order;

use php2steblya\order\OrderData_adres;

class OrderData_telegram
{
	public static function get($telegram)
	{
		$telegram = str_replace('https://t.me/', '', $telegram);
		$telegram = str_replace('@', '', $telegram);
		$telegram = strtolower($telegram);
		if (!preg_match('/^[a-z0-9_.-]+$/', $telegram)) {
			return '';
		} else {
			return $telegram;
		}
	}

	public static function getMessageForChannel($od)
	{
		$message = [];
		$products = [];
		foreach ($od['payment']['products'] as $product) {
			$props = [];
			foreach ($product['options'] as $option) {
				switch ($option['option']) {
					case 'фор мат':
					case 'формат':
						$props[0] = $option['variant'];
						break;
					case 'выебри карточку':
					case 'карточка':
						$props[1] = $option['variant'];
						break;
				}
			}
			$products[] = $product['name'] . ($product['isVitrina'] ? ' с витрины' : '') . ' (' . (!empty($props) ? implode(' ', $props) . ', ' : '') . $product['quantity'] . ' шт) - ' . $product['amount'] . ' р.';
		}
		//товары
		$message[] = '🏪 <b>Магазин:</b> ' . $od['site'];
		$message[] = '🌸 <b>Товары:</b>';
		foreach ($products as $product) {
			$message[] = $product;
		}
		//lovixlube
		if ($od['lovixlube']) {
			$message[] = '❤️ Добавить Lovix';
		}
		//заказчик
		$message[] = '🙎‍♂️ <b>Заказчик:</b>';
		$message[] = $od['name_zakazchika'] . ($od['messenger_zakazchika'] ? ' (@' . $od['messenger_zakazchika'] . ')' : '');
		$message[] = $od['phone_zakazchika'];
		if ($od['onanim']) $message[] = '<i>(аноним)</i>';
		//получатель
		if ($od['name_poluchatelya'] || $od['phone_poluchatelya']) {
			$message[] = '🙎 <b>Получатель:</b>';
			if ($od['name_poluchatelya']) $message[] = $od['name_poluchatelya'];
			if ($od['phone_poluchatelya']) $message[] = $od['phone_poluchatelya'];
		}
		//доставка
		foreach ($od['payment']['products'] as $product) {
			if ($product['isDonat']) continue;
			$message[] = '🏠 <b>Доставка:</b>';
			if ($od['uznat_adres_u_poluchatelya']) {
				$message[] = 'узнать адрес у получателя';
			} else {
				if (OrderData_adres::getText($od)) $message[] = OrderData_adres::getText($od);
			}
			$message[] = date('d.m.Y', strtotime($od['dostavka_date'])) . ' ' . $od['dostavka_interval'];
			break;
		}
		// коммент флористу
		if ($od['comment_florist']) {
			$message[] = '💬 <b>Комментарий флористу:</b>';
			$message[] = $od['comment_florist'];
		}
		//коммент курьеру
		if ($od['comment_courier']) {
			$message[] = '💬 <b>Комментарий курьеру:</b>';
			$message[] = $od['comment_courier'];
		}
		//текст в карточку
		if ($od['text_v_kartochku']) {
			$message[] = '💬 <b>Текст в карточку:</b>';
			$message[] = $od['text_v_kartochku'];
		}
		// сводка
		$message[] = '💵 <b>Сумма заказа:</b> ' . $od['payment']['amount'] . ' р.';
		$message[] = '⏱ <b>Время заказа:</b> ' . date('d.m.Y H:i', strtotime($od['date']));
		$message[] = $od['paid'] ? '✅ Оплачен' : '⛔️ Не оплачен';
		// utm
		$utm = [
			'keyword' 	=> $od['utm_term'],
			'source'	=> $od['utm_source'],
			'medium'	=> $od['utm_medium'],
			'content'	=> $od['utm_content'],
			'campaign'	=> $od['utm_campaign']
		];
		foreach ($utm as $key => $value) {
			if ($value) $message[] = '<b>utm-' . $key . ':</b> ' . $value;
		}
		// промокод
		if ($od['payment']['promocode']) {
			$message[] = '🛍 <b>Промокод:</b> "' . $od['payment']['promocode'] . '" (' . $od['payment']['discount'] . ' р.)';
		}

		return implode("\r\n", $message);
	}
}
