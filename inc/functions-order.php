<?
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/functions-apiRetailCrm.php';

function prepareOrderAfterTilda($site, $orderRaw)
{
	/**
	 * обрабатываем данные из Тильды
	 * приводим их в надлежащий для передачи в срм вид
	 */
	global $log;
	$order = [
		'zakazchik' => [
			'name' => parseName($orderRaw['name-zakazchika']),
			'phone' => $orderRaw['phone-zakazchika'],
			'messenger' => [
				[
					'vendor' => 'telegram',
					'value' => $orderRaw['messenger-zakazchika']
				]
			],
			'onanim' => $orderRaw['onanim'] ? true : false,
		],
		'poluchatel' => [
			'name' => $orderRaw['name-poluchatelya'] ? $orderRaw['name-poluchatelya'] : '',
			'phone' => $orderRaw['phone-poluchatelya'] ? $orderRaw['phone-poluchatelya'] : '',
			'isZakazchik' => $orderRaw['phone-poluchatelya'] == $orderRaw['phone-zakazchika']
		],
		'dostavka' => [
			'adres' => [
				'text' => buildAdres($orderRaw),
				'herZnaet' => $orderRaw['uznat-adres-u-poluchatelya'] ? true : false
			],
			'type' => 'Доставка курьером',
			'date' => parseDostavkaDate($orderRaw['dostavka-date']),
			'interval' => $orderRaw['dostavka-interval'],
			'price' => $orderRaw['dostavka-price'],
			'auto' => isAutoDelivery($orderRaw['payment']['products'])
		],
		'comments' => [
			'florist' => $orderRaw['florist-comment'] ? $orderRaw['florist-comment'] : '',
			'courier' => $orderRaw['courier-comment'] ? $orderRaw['courier-comment'] : ''
		],
		'contents' => [
			'offers' => $orderRaw['payment']['products'],
			'bukets' => parseBukets($site, $orderRaw['payment']['products']),
			'cardText' => $orderRaw['text-v-kartochku'] ? $orderRaw['text-v-kartochku'] : ''
		],
		'items' => parseItems($site, $orderRaw['payment']['products']),
		'payments' => parsePayment($orderRaw['payment']),
		'analytics' => [
			'utm' => [
				'source' => $orderRaw['utm_source'] ? $orderRaw['utm_source'] : '',
				'medium' => $orderRaw['utm_medium'] ? $orderRaw['utm_medium'] : '',
				'campaign' => $orderRaw['utm_campaign'] ? $orderRaw['utm_campaign'] : '',
				'content' => $orderRaw['utm_content'] ? $orderRaw['utm_content'] : '',
				'term' => $orderRaw['utm_term'] ? $orderRaw['utm_term'] : ''
			],
			'yandex' => [
				'clientId' => $orderRaw['ya-client-id']
			],
			'otkudaUznal' => $orderRaw['otkuda-uznal-o-nas'] ? $orderRaw['otkuda-uznal-o-nas'] : ''
		]
	];
	$log['orderData']['prepared'] = $order;
	return $order;
}
function parsePayment($payment)
{
	/**
	 * собираем массив для платежа
	 */
	$pays[] = [
		'externalId' => $payment['systranid'],
		'amount' => $payment['amount'],
		'paidAt' => date('Y-m-d H:i:s'),
		'type' => 'site',
		'status' => 'paid'
	];
	return $pays;
}
function parseItems($site, $products)
{
	/**
	 * формируем список товаров в заказе
	 * принудительно добавляем транспортировочное и уменьшаем стоимость первого товара на ценц транспортировочного + цена доставки
	 */
	$items = [];
	for ($i = 0; $i < count($products); $i++) {
		$items[] = [
			'offer' => [
				'externalId' => $products[$i]['externalid']
			],
			'properties' => itemProperties($products[$i]),
			'productName' => $products[$i]['name'],
			'quantity' => $products[$i]['quantity'],
			'purchasePrice' => !$i && $site == '2steblya' ? $products[$i]['amount'] - 1000 : $products[$i]['amount'] //транспортировочное (500-доставка + 500-упак,ленты,петушок,карточка)
		];
	}
	$items[] = transportItem($site);
	return $items;
}
function transportItem($site)
{
	/**
	 * добавляем транспортировочное
	 */
	switch ($site) {
		case '2steblya':
			return [
				'productName' => 'Транспортировочное',
				'externalId' => 214,
				'offer' => [
					'externalId' => 214
				],
				'quantity' => 1,
				'purchasePrice' => 500,
				'initialPrice' => 500
			];
			break;
		case 'Stay True Flowers':
			return [
				'productName' => 'Упаковка',
				'id' => 805,
				'externalIds' => [
					[
						'code' => 'default',
						'value' => '223'
					]
				],
				'offer' => [
					'id' => 1258,
					'externalId' => '223'
				],
				'quantity' => 2,
				'purchasePrice' => 100,
				'initialPrice' => 100
			];
			break;
	}
}
function itemProperties($product)
{
	/**
	 * формируем свойства товара (формат, выебри карточку и пр.)
	 * если товар с витрины, добавляем соответствующее свойство
	 */
	$props = [];
	foreach ($product['options'] as $option) {
		$props[] = [
			'name' => $option['option'],
			'value' => $option['variant']
		];
	}
	if (substr($product['sku'], -1) == 'v') {
		$props[] = [
			'name' => 'готов',
			'value' => 'на витрине'
		];
	}
	$props[] = [
		'name' => 'цена',
		'value' => $product['price']
	];
	return $props;
}
function buildAdres($orderRaw)
{
	/**
	 * собираем адрес в строку из отдельных полей
	 */
	$a = [
		'region' => $orderRaw['adres-poluchatelya-region'], //регион
		'city' => $orderRaw['adres-poluchatelya-city'], //город
		'street' => $orderRaw['adres-poluchatelya-street'], //улица
		'building' => $orderRaw['adres-poluchatelya-dom'], //дом
		'housing' => $orderRaw['adres-poluchatelya-korpus'], //корпус
		'house' => $orderRaw['adres-poluchatelya-stroenie'], //строение
		'flat' => $orderRaw['adres-poluchatelya-kvartira'], //квартира
		'floor' => $orderRaw['adres-poluchatelya-etazh'], //этаж
		'block' => $orderRaw['adres-poluchatelya-podezd'] //подъезд
	];
	$adres = [];
	if ($a['region']) $adres[] = $a['region'];
	if ($a['city']) $adres[] = 'г. ' . $a['city'];
	if ($a['street']) $adres[] = $a['street'];
	if ($a['building']) $adres[] = $a['building'] . ($a['housing'] ? 'к' . $a['housing'] : '') . ($a['house'] ? 'с' . $a['house'] : '');
	if ($a['flat']) $adres[] = 'кв. ' . $a['flat'];
	if ($a['block']) $adres[] = 'подъезд ' . $a['block'];
	if ($a['floor']) $adres[] = 'этаж ' . $a['floor'];
	return implode(', ', $adres);
}
function isAutoDelivery($offers)
{
	/**
	 * ищем в товарах такой, который всегда точно отправляется только на авто
	 */
	$autoFormats = ['коробка', 'корзинка', 'корзина', 'букет-гигант', 'корзинища'];
	foreach ($offers as $offer) {
		foreach ($offer['options'] as $option) {
			if (in_array($option['variant'], $autoFormats)) return true;
		}
	}
	return false;
}
function parseBukets($site, $offers)
{
	/**
	 * собираем строку "букеты в заказе"
	 */
	if ($site != '2steblya') return '';
	$siteFormats = [
		'2steblya' => 'фор мат',
		'Stay True Flowers' => 'формат'
	];
	$bukets = [];
	foreach ($offers as $offer) {
		$format = '';
		foreach ($offer['options'] as $option) {
			if ($option['option'] != $siteFormats[$site]) continue;
			$format = $option['variant'];
		}
		$bukets[] = $offer['name'] . ' - ' . $format . ' (' . $offer['quantity'] . ' шт)';
	}
	return implode(', ', $bukets);
}
function parseDostavkaDate($date)
{
	/**
	 * обрабатываем дату доставки
	 */
	$date = explode('-', $date);
	return $date[2] . '-' . $date[1] . '-' . $date[0];
}
function parseName($name)
{
	/**
	 * пытаемся определить ФИО
	 */
	$fio = explode(' ', $name);
	switch (count($fio)) {
		case 1:
			return [
				'firstName' => $fio[0],
				'lastName' => '',
				'patronymic' => ''
			];
			break;
		case 2:
			return [
				'firstName' => array_shift($fio),
				'lastName' => $fio[0],
				'patronymic' => ''
			];
			break;
		default:
			return [
				'lastName' => array_shift($fio),
				'patronymic' => array_pop($fio),
				'firstName' => implode(' ', $fio)
			];
	}
}
function getCustomer($phone)
{
	/**
	 * запрашиваем у срм клиента по номеру телефона
	 * если такого клиента нет, возвращяем []
	 * если есть, возвращаем [id=>id]
	 * этого достаточно для того, чтобы связать заказ с клиентом в срм
	 */
	global $log;
	$customerResponse = apiGET('customers', ['filter[name]' => $phone]);
	$log['customerResponse'] = $customerResponse;
	if (!$customerResponse->success) return null;
	if (!$customerResponse->pagination->totalCount) return null;
	return $customerResponse->customers[0];
}
