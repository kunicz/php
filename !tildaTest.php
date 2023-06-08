<?
require_once __DIR__ . '/vendor/autoload.php';

use php2steblya\File;

$f = new File(__DIR__ . '/test.txt');
$f->write(time());
$f->append($f->url);
die();

/*
require_once __DIR__ . '/inc/headers-cors.php';
require_once __DIR__ . '/inc/functions-order.php';

$log = [];
$orderRaw = print_r_reverse(file_get_contents(__DIR__ . '/tildaOrderLast-2steblya.txt'));
$log['orderData']['tilda'] = $orderRaw;
$order = prepareOrderAfterTilda('2steblya', $orderRaw);
$customer = getCustomer($order['zakazchik']['phone']);
$order = [
	'externalId' => 'php_' . time(),
	'orderMethod' => 'php',
	'customer' => $customer ? ['id' => $customer->id] : [],
	'firstName' => $order['zakazchik']['name']['firstName'],
	'lastName' => $order['zakazchik']['name']['lastName'],
	'patronymic' => $order['zakazchik']['name']['patronymic'],
	'phone' => $order['zakazchik']['phone'],
	'customerComment' => $order['comments']['courier'],
	'managerComment' => $order['comments']['florist'],
	'delivery' => [
		'address' => [
			'text' => $order['dostavka']['adres']['text']
		],
		'date' => $order['dostavka']['date'],
		'time' => [
			'custom' => $order['dostavka']['interval']
		],
		'cost' => $order['dostavka']['price']
	],
	'items' => $order['items'],
	'payments' => $order['payments'],
	'customFields' => [
		'name_poluchatelya' => $order['poluchatel']['name'],
		'phone_poluchatelya' => $order['poluchatel']['phone'],
		'stoimost_dostavki_iz_tildy' => $order['dostavka']['price'],
		'bukety_v_zakaze' => $order['contents']['bukets'],
		'text_v_kartochku' => $order['contents']['cardText'],
		'zakazchil_poluchatel' => $order['poluchatel']['isZakazchik'],
		'messenger-zakazchika' => $order['zakazchik']['messenger'][0]['value'],
		'otkuda_o_nas_uznal' => $order['analytics']['otkudaUznal'],
		'onanim' => $order['zakazchik']['onanim'],
		'ya_client_id_order' => $order['analytics']['yandex']['clientId']
	],
	'source' => [
		'source' => $order['analytics']['utm']['source'],
		'medium' => $order['analytics']['utm']['medium'],
		'campaign' => $order['analytics']['utm']['campaign'],
		'keyword' => $order['analytics']['utm']['term'],
		'content' => $order['analytics']['utm']['content']
	]
];
$log['orderData']['crm'] = $order;
$args = [
	'site' => $_SERVER['magazin_stf_id'],
	'order' => urlencode(json_encode($order))
];
//$log['orderResponse'] = apiPOST('orders/create', urlencode(json_encode($args)));
die(json_encode($log));*/
