<?

namespace php2steblya\scripts;

use php2steblya\Name;
use php2steblya\Logger;
use php2steblya\ApiRetailCrmResponse_customers_get as Customers_get;
use php2steblya\ApiRetailCrmResponse_customers_edit as Customers_edit;

class ClearCustomersAdres
{
	private $source;
	public $log;

	public function init()
	{
		$this->source = 'clear customers adres';
		$this->log = new Logger($this->source);
		$this->clear();
		//$this->log->writeSummary();
	}

	/**
	 * получаем всех клиентов рекурсивно
	 * очищаем адрес каждому, у кого он есть
	 */
	private function clear($page = 1)
	{
		$args = [
			'limit' => 100,
			'page' => $page
		];
		$customers = new Customers_get($this->source, $args);

		$editedCustomers = [];
		foreach ($customers->get() as $customer) {
			if (!isset($customer->address)) continue;
			$id = $customer->id;
			$name = new Name($customer->firstName, $customer->lastName, $customer->patronymic);
			$args = [
				'by' => 'id',
				'site' => $customer->site,
				'customer' => json_encode([
					'address' => [
						'text' => ''
					]
				])
			];
			$customer = new Customers_edit($this->source, $args, $id, $name->getName());
			$editedCustomers[] = [$id, $name->getName()];
		}

		$this->log->push($page . '. customers', ['collected' => $customers->getLog(), 'edited' => $editedCustomers]);

		if ($customers->api->getCurrentPage() == $customers->api->getPageCount()) return;
		$this->clear($customers->api->getCurrentPage() + 1);
	}
}
