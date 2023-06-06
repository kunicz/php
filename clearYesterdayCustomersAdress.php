<?
require __DIR__ . '/inc/functions-apiRetailCrm.php';

/**
 * получаем заказы за вчера
 * находим клиентов, пробегаемся по ним и очищаем адреса
 * cron: каждый день в 0:30
 */

$log = [];
iterateOrders();
die(json_encode($log));

function iterateOrders($page = 1)
{
	global $log;
	$yesterday = date('Y-m-d', strtotime('-1 day'));
	$log['yesterday'] = $yesterday;
	$ordersRequest = apiGET('orders', ['filter[createdAtFrom]' => $yesterday, 'filter[createdAtTo]' => $yesterday, 'page' => $page]);
	$log['pages'] = $ordersRequest->pagination->totalPageCount;
	$log[$page]['orders'] = $ordersRequest;
	$log[$page]['success'] = $ordersRequest->success;
	if (!$ordersRequest->success) return;
	if (!$ordersRequest->pagination->totalCount) return;
	$log[$page]['editedCustomers'] = [];
	foreach ($ordersRequest->orders as $order) {
		$args = [
			'by' => 'id',
			'site' => $order->site,
			'customer' => urlencode(json_encode(['address' => ['text' => '']]))
		];
		$customerResponse = apiPOST('customers/' . $order->customer->id . '/edit', $args);
		$log[$page]['editedCustomers'][] = $customerResponse;
	}
	if ($ordersRequest->pagination->totalPageCount == $ordersRequest->pagination->currentPage) return;
	iterateOrders($ordersRequest->pagination->currentPage + 1);
}
