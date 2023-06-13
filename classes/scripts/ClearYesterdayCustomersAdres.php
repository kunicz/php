<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\ApiRetailCrm as Api;
use php2steblya\LoggerException as Exception;

class ClearYesterdayCustomersAdres
{
	public $log;
	private $response;
	private $orders;
	private array $customersIds;

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
		if (!$this->response->getCount()) {
			$this->log->pushNote('orders not found');
			return;
		}
		$this->clearAdreses();
		$this->log->setRemark(implode(',', $this->customersIds));
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
			$api = new Api();
			$api->get('orders', $args);
			$this->log->insert('collectOrders');
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
			if (!$api->getCount()) {
				$this->log->pushNote('no orders found');
			}
			$this->response = $api;
			$this->orders = $api->response->orders;
		} catch (Exception $e) {
			$e->abort($this->log);
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
			foreach ($this->orders as $order) {
				$args = [
					'by' => 'id',
					'site' => $order->site,
					'customer' => json_encode(['address' => ['text' => '']])
				];
				$api = new Api();
				$api->post('customers/' . $order->customer->id . '/edit', $args);
				$this->log->insert('clearAdreses[' . $order->customer->id . ']');
				$this->log->push('queryString', $args);
				$this->log->push('response', $api->response);
				if ($api->hasErrors()) {
					throw new Exception($api->getError());
				}
				$this->customersIds[] = $order->customer->id;
			}
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
}
