<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiRetailCrm as Api;
use php2steblya\LoggerException as Exception;

/**
 * создаем заказ для Веры Александровны в среду (доставка на четверг)
 * cron: по средам в 10:10
 */

class VeraEveryThursday
{
	public $log;

	public function init(): void
	{
		try {
			$this->log = new Logger('vera thursday order');
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
			$api = new Api();
			$api->post('orders/create', $args);
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			$this->log->push('orderData', $orderData->getCrm(false));
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
			$this->log->setRemark($api->response->order->id);
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
}
