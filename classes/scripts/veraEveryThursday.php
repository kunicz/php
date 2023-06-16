<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiRetailCrmResponse_orders_create as Order_create;

/**
 * создаем заказ для Веры Александровны в среду (доставка на четверг)
 * cron: по средам в 10:10
 */

class VeraEveryThursday
{
	public $log;

	public function init(): void
	{
		$source = 'vera thursday order';
		$this->log = new Logger($source);
		$orderData = new OrderData($_ENV['site_stf_id']);
		$orderData->setCustomerId(551);
		$orderData->zakazchik->setFirstName('Вера');
		$orderData->zakazchik->setPatronymic('Александровна');
		$orderData->zakazchik->setPhone($_ENV['vera_phone_zakazchika']);
		$orderData->dostavka->setRegion('Московская область');
		$orderData->dostavka->setCity('Химки');
		$orderData->dostavka->setStreet($_ENV['vera_street']);
		$orderData->dostavka->setBuilding($_ENV['vera_building']);
		$orderData->dostavka->setFlat($_ENV['vera_flat']);
		$orderData->dostavka->setFloor(2);
		$orderData->dostavka->setDate(date('Y-m-d', strtotime('+1 day')));
		$orderData->dostavka->setCost(700);
		$orderData->dostavka->setNetCost(700);
		$orderData->poluchatel->setName('Алена');
		$orderData->poluchatel->setPhone($_ENV['vera_phone_poluchaelya']);
		$orderData->items->pushTransportItem();
		$args = [
			'site' => $_ENV['site_stf_id'],
			'order' => $orderData->getCrm()
		];
		$order = new Order_create($source, $args);
		$this->log->push('1. order create', $order->getLog());
		$this->log->setRemark($order->getRemark());
		$this->log->writeSummary();
	}
}
