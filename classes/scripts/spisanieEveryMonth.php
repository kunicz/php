<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiRetailCrmResponse_orders_get as Order_get;
use php2steblya\ApiRetailCrmResponse_orders_edit as Order_edit;
use php2steblya\ApiRetailCrmResponse_orders_create as Order_create;

/*
	помечаем списание за прошедший месяц как "выполнено"
	создаем новое списание на следующий месяц
	cron: каждое первое число каждого месяца в 10:10
*/

class SpisanieEveryMonth
{
	public $log;
	private $source;
	private $customerId;

	public function init(): void
	{
		$this->source = 'spisanie every month';
		$this->log = new Logger($this->source);
		$this->customerId = 1383;
		$this->spisanieOld();
		$this->spisanieNew();
		$this->log->writeSummary();
	}
	private function spisanieOld()
	{
		/**
		 * получаем старое списание
		 */
		$currentMonthFirstDay = strtotime(date('Y-m-01'));
		$createdAtFrom = date('Y-m-d', strtotime('-1 month', $currentMonthFirstDay));
		$createdAtTo = date('Y-m-d', strtotime('-1 day', $currentMonthFirstDay));
		$args = [
			'filter' => [
				'customerId' => $this->customerId,
				'createdAtFrom' => $createdAtFrom,
				'createdAtTo' => $createdAtTo
			]
		];
		$orderOld = new Order_get($this->source, $args);
		$this->log->push('1. get old spisanie', $orderOld->getLog());
		/**
		 * обновляем статус
		 */
		$args = [
			'by' => 'id',
			'site' => $_ENV['site_ostatki_id'],
			'order' => json_encode(['status' => 'complete'])
		];
		$orderOld = new Order_edit($this->source, $args, $orderOld->getOrdersIds()[0]);
		$this->log->push('2. edit old spisanie', $orderOld->getLog());
	}
	private function spisanieNew()
	{
		/**
		 * создаем новое списание
		 */
		$orderData = new OrderData($_ENV['site_ostatki_id']);
		$orderData->setCustomerId($this->customerId);
		$orderData->dostavka->setDate(date('Y-m-d', strtotime('+1 day', strtotime(date('Y-m-t')))));
		$orderData->zakazchik->setFirstName('списание');
		$orderData->setStatus('sborka');
		$orderData->addCustomField('florist', 'boss');
		$args = [
			'site' => $_ENV['site_ostatki_id'],
			'order' => $orderData->getCrm()
		];
		$orderNew = new Order_create($this->source, $args);
		$this->log->push('3. create new spisanie', $orderNew->getLog());
		$this->log->setRemark($orderNew->getRemark());
	}
}
