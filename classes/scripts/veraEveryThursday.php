<?

require_once __DIR__ . '/dotenv.php';

use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiRetailCrm_orders as apiOrders;

/**
 * создаем заказ для Веры Александровны в среду (доставка на четверг)
 * cron: по средам в 10:10
 */

$log = new Logger('vera thursday order');

$orderData = new OrderData();
$orderData->setSite('Stay True flowers');
$orderData->setCustomerId(551);
$orderData->zakazchik->setFirstName('Вера');
$orderData->zakazchik->setPatronymic('Александровна');
$orderData->zakazchik->setPhone($_SERVER['vera_phone_zakazchika']);
$orderData->dostavka->setRegion('Московская область');
$orderData->dostavka->setCity('Химки');
$orderData->dostavka->setStreet($_SERVER['vera_street']);
$orderData->dostavka->setBuilding($_SERVER['vera_building']);
$orderData->dostavka->setFlat($_SERVER['vera_flat']);
$orderData->dostavka->setFloor(2);
$orderData->dostavka->setDate(date('Y-m-d', strtotime('+1 day')));
$orderData->dostavka->setCost(700);
$orderData->dostavka->setNetCost(700);
$orderData->poluchatel->setName('Алена');
$orderData->poluchatel->setPhone($_SERVER['vera_phone_poluchaelya']);

$order = new apiOrders();
$args = [
	'site=' . $orderData->getSite(),
	'order=' . $orderData->getCrm()
];
$order->post('orders/create', $args);
$log->push('orderResponse', $order);
if (!$order->response->success) {
	$log->pushError($order->response->getError());
} else {
	$log->setRemark($order->response->order->id);
}
$log->writeSummary();
die($log->getJson());
