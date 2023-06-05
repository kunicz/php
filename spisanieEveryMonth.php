<?
require __DIR__ . '/inc/functions-api.php';

/*
	помечаем списание за прошедший месяц как "выполнено"
	создаем новое списание на следующий месяц
	cron: каждое первое число каждого месяца в 10:00
*/
$log = [];
spisanieOld();
spisanieNew();
echo json_encode($log);
die();

function spisanieOld()
{
	global $log;
	/**
	 * получаем
	 */
	$currentMonthFirstDay = strtotime(date('Y-m-01'));
	$createdAtFrom = date('Y-m-d', strtotime('-1 month', $currentMonthFirstDay));
	$createdAtTo = date('Y-m-d', strtotime('-1 day', $currentMonthFirstDay));
	$log['createdAtFrom'] = $createdAtFrom;
	$log['createdAtTo'] = $createdAtTo;
	$args = [
		'filter[customerId]' => 1383,
		'filter[createdAtFrom]' => $createdAtFrom,
		'filter[createdAtTo]' => $createdAtTo
	];
	$orderOldRequest = apiGET('orders', $args);
	$log['orderOldRequest'] = $orderOldRequest;
	if (!$orderOldRequest->pagination->totalCount) return;
	/**
	 * обновляем статус
	 */
	$args = [
		'by' => 'id',
		'site' => $orderOldRequest->orders[0]->site,
		'order' => urlencode(json_encode(['status' => 'complete']))
	];
	$log['spisanieOldResponse'] = apiPOST('orders/' . $orderOldRequest->orders[0]->id . '/edit', $args);
}
function spisanieNew()
{
	global $log;
	$order = [
		'externalId' => 'php_' . time(),
		'orderMethod' => 'php',
		'customer' => [
			'id' => 1383
		],
		'delivery' => [
			'date' => date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-t')))),
		],
		'firstName' => 'списание',
		'status' => 'sborka',
		'customFields' => [
			'florist' => 'boss'
		]

	];
	$args = [
		'site' => $_SERVER['magazin_ostatki_id'],
		'order' => urlencode(json_encode($order))
	];
	$orderNewResponse = apiPOST('orders/create', $args);
	$log['orderNewResponse'] = $orderNewResponse;
}
