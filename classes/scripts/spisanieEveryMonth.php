<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiRetailCrm as Api;
use php2steblya\LoggerException as Exception;

/*
	помечаем списание за прошедший месяц как "выполнено"
	создаем новое списание на следующий месяц
	cron: каждое первое число каждого месяца в 10:10
*/

class SpisanieEveryMonth
{
	public $log;
	private $customerId;

	public function init(): void
	{
		$this->log = new Logger('spisanie every month');
		$this->customerId = 1383;
		$this->spisanieOld();
		$this->spisanieNew();
	}
	private function spisanieOld()
	{
		try {
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
			$api = new Api();
			$api->get('orders', $args);
			$this->log->insert('1. get old spisanie');
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
			if (!$api->getCount()) {
				$this->log->pushNote('old spisanie not found');
				return;
			}
			$orderId = $api->response->orders[0]->id;
			/**
			 * обновляем статус
			 */
			$args = [
				'by' => 'id',
				'site' => $api->response->orders[0]->site,
				'order' => json_encode(['status' => 'complete'])
			];
			$api = new Api();
			$api->post('orders/' . $orderId . '/edit', $args);
			$this->log->insert('2. old spisanie complete');
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
	private function spisanieNew()
	{
		try {
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
			$api = new Api();
			$api->post('orders/create', $args);
			$this->log->insert('3. create new spisanie');
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			$this->log->push('orderData', $orderData->getCrm(false));
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
}
