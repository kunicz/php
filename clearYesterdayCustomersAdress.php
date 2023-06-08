<?
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/functions-apiRetailCrm.php';

/**
 * получаем заказы за вчера
 * находим клиентов, пробегаемся по ним и очищаем адреса
 * cron: каждый день в 1:30
 */

$log = [];
iterateOrders();
$log['summary'] = getLogSummary($log, 'адреса клиентов за вчера', 'удалены', $log['customers'] ? implode(',', $log['customers']) : '');
writeLog($log['summary']);
die(json_encode($log));

function iterateOrders($page = 1)
{
	global $log;
	$yesterday = date('Y-m-d', strtotime('-1 day'));
	$ordersRequest = apiGET('orders', ['filter[createdAtFrom]' => $yesterday, 'filter[createdAtTo]' => $yesterday, 'page' => $page]);
	$log['ordersRequest'] = $ordersRequest;
	if (!$ordersRequest->success) {
		apiErrorLog($log, $ordersRequest, 'ordersRequest');
		return;
	}
	if (!$ordersRequest->pagination->totalCount) {
		$log['errors'][] = 'заказов не было';
		return;
	}
	$log['editedCustomers'] = [];
	foreach ($ordersRequest->orders as $order) {
		$args = [
			'by' => 'id',
			'site' => $order->site,
			'customer' => urlencode(json_encode(['address' => ['text' => '']]))
		];
		$customerResponse = apiPOST('customers/' . $order->customer->id . '/edit', $args);
		$log['editedCustomers'][] = $customerResponse;
		if (!$customerResponse->success) {
			apiErrorLog($log, $customerResponse, 'customerRespose for order ' . $order->id);
		} else {
			$log['customers'][] = $order->customer->id;
		}
	}
	if ($ordersRequest->pagination->totalPageCount == $ordersRequest->pagination->currentPage) return;
	iterateOrders($ordersRequest->pagination->currentPage + 1);
}
