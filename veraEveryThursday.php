<?
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/functions-order.php';
require_once __DIR__ . '/inc/functions-apiRetailCrm.php';

/**
 * создаем заказ для Веры Александровны в среду (доставка на четверг)
 * cron: по средам в 10:10
 */

$log = [];
$order = [
	'externalId' => 'php_' . time(),
	'orderMethod' => 'php',
	'customer' => [
		'id' => 551
	],
	'firstName' => 'Вера',
	'patronymic' => 'Александровна',
	'phone' => $_SERVER['vera_phone_zakazchika'],
	'delivery' => [
		'address' => [
			'text' => 'Московская область, г. Химки, ' . $_SERVER['vera_street'] . ' ' . $_SERVER['vera_building'] . ', кв.' . $_SERVER['vera_flat'] . ', этаж 2'
		],
		'date' => date('Y-m-d', strtotime('+1 day')),
		'cost' => 700,
		'netCost' => 700
	],
	'items' => [
		transportItem('Stay True Flowers')
	],
	'customFields' => [
		'name_poluchatelya' => 'Алена',
		'phone_poluchatelya' => $_SERVER['vera_phone_poluchaelya'],
		'stoimost_dostavki_iz_tildy' => 700
	]
];
$args = [
	'site' => $_SERVER['magazin_stf_id'],
	'order' => urlencode(json_encode($order))
];
$orderResponse = apiPOST('orders/create', $args);
$log['orderResponse'] = $orderResponse;
apiErrorLog($log, $orderResponse);
$log['summary'] = getLogSummary($log, 'заказ для Веры', 'создан', $orderResponse->order->id);
writeLog($log['summary']);
die(json_encode($log));
