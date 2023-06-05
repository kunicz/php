<?
require __DIR__ . '/inc/functions-api.php';

/* cron выполняет скрипт по средам в 10 утра */

$order = [
	'externalId' => 'php_' . time(),
	'orderMethod' => 'php',
	'customer' => [
		'id' => 551
	],
	'firstName' => 'Вера',
	'patronymic' => 'Александровна',
	'phone' => $_SERVER['vera_phone_customer'],
	'delivery' => [
		'address' => [
			'region' => 'Московская область',
			'city' => 'г. Химки',
			'street' => $_SERVER['vera_street'],
			'building' => $_SERVER['vera_building'],
			'flat' => $_SERVER['vera_flat'],
			'floor' => '2'
		],
		'date' => date('Y-m-d', strtotime('+1 day')),
		'cost' => 700,
		'netCost' => 700
	],
	'customFields' => [
		'name_poluchatelya' => 'Алена',
		'phone_poluchatelya' => $_SERVER['vera_phone_addressee'],
		'stoimost_dostavki_iz_tildy' => 700
	]
];

$args = [
	'site' => $_SERVER['magazin_stf_id'],
	'order' => urlencode(json_encode($order))
];
apiPOST('orders/create', $args);
die();
