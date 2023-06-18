<?

namespace php2steblya;

use php2steblya\Logger;

class ApiRetailCrmResponse_customers_get extends ApiRetailCrmResponse
{
	private array $customersIds;

	public function __construct($source, array $args, string $name = '')
	{
		$this->log = new Logger('get customers');
		parent::__construct($source);
		$this->method = 'customers';
		$this->args = $args;
		$this->request('get');
		if (!$this->api->getCount()) {
			$this->log->pushNote('no customers found' . ($name ? '(' . $name . ')' : ''));
		}
		foreach ($this->api->response->customers as $customer) {
			$this->customersIds[] = $customer->id;
		}
	}
	public function has()
	{
		return $this->api->getCount() ? true : false;
	}
	public function get()
	{
		return $this->api->response->customers;
	}
	public function getIds(): array
	{
		return $this->customersIds;
	}
	public function getRemark()
	{
		if (count($this->customersIds) > 1) {
			return 'customers (' . implode(',', $this->customersIds) . ')';
		} else {
			return 'customer (' . $this->customersIds[0] . ')';
		}
	}
}
