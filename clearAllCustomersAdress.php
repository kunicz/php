<?
require __DIR__ . '/inc/headers-cors.php';
require __DIR__ . '/inc/functions-api.php';

/*
на момент написания скрипта в базе было 800 клиентов (40 страниц по 20 клиентов)
сервер не успевал обработать всех в цикле, поэтому решил порциями обрабатывать
брал частями по 10 страниц (4 итерации)
*/

$log = [];
$pages = 10;
$iteration = 4;
for ($j = $pages * $iteration + 1 - $pages; $j <= $pages * $iteration; $j++) {
	iterateCustomers($j);
}
echo json_encode($log);
die();

function iterateCustomers($page = 1)
{
	global $log;
	$customersRequest = apiGET('customers', ['page' => $page]);
	$log['pages'] = $customersRequest->pagination->totalPageCount;
	$log[$page]['customers'] = $customersRequest;
	$log[$page]['success'] = $customersRequest->success;
	if (!$customersRequest->success) return;
	if (!$customersRequest->pagination->totalCount) return;
	$log[$page]['editedCustomers'] = [];
	foreach ($customersRequest->customers as $customer) {
		$args = [
			'by' => 'id',
			'site' => $customer->site,
			'customer' => urlencode(json_encode(['address' => ['text' => '']]))
		];
		$customerResponse = apiPOST('customers/' . $customer->id . '/edit', $args);
		$log[$page]['editedCustomers'][] = $customerResponse;
	}
	if ($customersRequest->pagination->totalPageCount == $customersRequest->pagination->currentPage) return;
	//iterateCustomers($customersRequest->pagination->currentPage + 1);
}
