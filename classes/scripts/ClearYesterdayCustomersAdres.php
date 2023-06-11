<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\ApiRetailCrm as api;

class ClearYesterdayCustomersAdres
{
	public $log;
	private $response;
	private $orders;

	/**
	 * получаем заказы за вчера
	 * находим клиентов, пробегаемся по ним и очищаем адреса
	 * cron: каждый день в 1:30
	 * 
	 * @return void Returns data of type void
	 */
	public function init(): void
	{
		$this->log = new Logger('clear yesterday customer\'s adreses');
		$this->collectOrders();
		if (!$this->response->getCount()) return;
		$this->clearAdreses();
		$this->log->writeSummary();
	}
	/**
	 * получаем заказы из срм
	 * 
	 * @return void Returns data of type void
	 */
	public function collectOrders(): void
	{
		try {
			$yesterday = date('Y-m-d', strtotime('-1 day'));
			$args = [
				'limit' => 100,
				'filter' => [
					'createdAtFrom' => $yesterday,
					'createdAtTo' => $yesterday
				]
			];
			$api = new api();
			$api->get('orders', $args);
			$this->log->push('queryString', $args, 0);
			$this->log->push('response', $api->response, 0);
			if ($api->hasErrors()) {
				throw new \Exception($api->getError());
			}
			if (!$api->getCount()) {
				$this->log->pushError('no orders found');
			}
			$this->response = $api;
			$this->orders = $api->response->orders;
		} catch (\Exception $e) {
			$this->log->pushError($e->getMessage());
			$this->log->writeSummary();
			die($this->log->getJson());
		}
	}
	/**
	 * очищаем адреса
	 * 
	 * @return void Returns data of type void
	 */
	public function clearAdreses(): void
	{
		try {
			$customersIds = [];
			foreach ($this->orders as $order) {
				$args = [
					'by' => 'id',
					'site' => $order->site,
					'customer' => urlencode(json_encode(['address' => ['text' => '']]))
				];
				$api = new api();
				$api->post('customers/' . $order->customer->id . '/edit', $args);
				$this->log->push('queryString', $args, 1);
				$this->log->push('response', $api->response, 1);
				if ($api->hasErrors()) {
					throw new \Exception($api->getError());
				}
				if (!$api->getCount()) {
					throw new \Exception('no customers found for order ' . $order->id);
				}
				$customersIds[] = $order->customer->id;
			}
			$this->log->setRemark(implode(',', $customersIds));
		} catch (\Exception $e) {
			$this->log->pushError($e->getMessage());
			$this->log->writeSummary();
			die($this->log->getJson());
		}
	}
}
