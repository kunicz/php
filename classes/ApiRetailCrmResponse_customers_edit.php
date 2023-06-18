<?

namespace php2steblya;

use php2steblya\Logger;

class ApiRetailCrmResponse_customers_edit extends ApiRetailCrmResponse
{
	public function __construct($source, array $args, $customerId, $name)
	{
		$this->log = new Logger('edit customer');
		parent::__construct($source);
		$this->log->push('customerData', json_decode($args['customer'], true));
		$this->log->push('customerName', $name);
		$this->method = 'customers/' . $customerId . '/edit';
		$this->args = $args;
		$this->request('post');
	}
	public function getRemark()
	{
		return 'customer (' . $this->response->id . ')';
	}
}
