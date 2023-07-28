<?

namespace php2steblya\scripts;

use php2steblya\Name;
use php2steblya\Logger;
use php2steblya\OrderData_zakazchik_telegram as Telegram;
use php2steblya\ApiRetailCrmResponse_orders_get as Orders_get;
use php2steblya\ApiRetailCrmResponse_customers_edit as Customers_edit;

class UpdateYesterdayCustomers
{
	private $source;
	public $log;
	private $orders;

	/**
	 * получаем заказы за вчера
	 * находим клиентов, пробегаемся по ним и очищаем адреса
	 * cron: каждый день в 1:30
	 */
	public function init(): void
	{
		$this->source = 'update yesterday customers';
		$this->log = new Logger($this->source);
		$this->collectOrders();
		if (!$this->orders->has()) return;
		$this->clearAdreses();
		$this->log->writeSummary();
	}
	/**
	 * получаем заказы из срм
	 */
	public function collectOrders(): void
	{
		$args = [
			'limit' => 100,
			'filter' => [
				'createdAtFrom' => date('Y-m-d', strtotime('-8 day')),
				'createdAtTo' => date('Y-m-d', strtotime('-1 day'))
			]
		];
		$orders = new Orders_get($this->source, $args);
		$this->orders = $orders;
		$this->log->push('1. collect orders', $orders->getLog());
	}
	/**
	 * очищаем адреса, переписываем телеграм, ya_client_id
	 * записываем "откуда узнал", если это первый заказ
	 */
	public function clearAdreses(): void
	{
		$this->log->insert('2. edited customers');
		$customersIds = [];
		foreach ($this->orders->get() as $order) {
			$name = new Name($order->customer->firstName, $order->customer->lastName, $order->customer->patronymic);
			$telegram = new Telegram($order->customFields->messenger_zakazchika);
			$customerId = $order->customer->id;
			$customerData = [
				'address' => [
					'text' => ''
				],
				'customFields' => [
					'telegram' => $telegram->get(),
					'ya_client_id' => $order->customFields->ya_client_id_order
				]
			];
			if (!$order->customer->ordersCount) {
				$customerData['customFields'][] = ['otkuda_uznal_o_nas' => $order->customFields->otkuda_o_nas_uznal];
			}
			$args = [
				'by' => 'id',
				'site' => $order->site,
				'customer' => json_encode($customerData)
			];
			$customer = new Customers_edit($this->source, $args, $customerId, $name->getName());
			$this->log->push($customerId, $customer->getLog());
			$customersIds[] = $customerId;
		}
		$this->log->setRemark(implode(',', $customersIds));
	}
}
