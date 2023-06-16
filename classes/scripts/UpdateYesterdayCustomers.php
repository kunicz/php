<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\OrderData_zakazchik_telegram as Telegram;
use php2steblya\ApiRetailCrmResponse_orders_get as Orders;
use php2steblya\ApiRetailCrmResponse_customers_edit as Customer;

class UpdateYesterdayCustomers
{
	private $source;
	public $log;
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
		$this->source = 'clear yesterday customer\'s adreses';
		$this->log = new Logger($this->source);
		$this->collectOrders();
		if (empty($this->orders)) {
			$this->log->pushNote('orders not found');
			return;
		}
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
		$yesterday = date('Y-m-d', strtotime('-1 day'));
		$args = [
			'limit' => 100,
			'filter' => [
				'createdAtFrom' => $yesterday,
				'createdAtTo' => $yesterday
			]
		];
		$orders = new Orders($this->source, $args);
		$this->orders = $orders->getOrders();
		$this->log->push('1. orders', $orders->getLog());
	}
	/**
	 * очищаем адреса, переписываем телеграм, ya_client_id
	 * записываем "откуда узнал", если это первый заказ
	 * 
	 * @return void Returns data of type void
	 */
	public function clearAdreses(): void
	{
		$this->log->insert('2. edited customers');
		$customersIds = [];
		foreach ($this->orders as $order) {
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
			$customer = new Customer($this->source, $args, $customerId, $name->getName());
			$this->log->push($customerId, $customer->getLog());
			$customersIds[] = $customerId;
		}
		$this->log->setRemark(implode(',', $customersIds));
	}
}

class Name
{
	private $name;

	public function __construct($firstName, $lastName, $patronymic)
	{
		$this->name = '';
		if ($lastName) $this->name .= $$lastName;
		if ($firstName) $this->name .= ' ' . $firstName;
		if ($patronymic) $this->name .= ' ' . $patronymic;
	}
	public function getName()
	{
		return trim($this->name);
	}
}
