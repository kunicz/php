<?

namespace php2steblya;

use php2steblya\Logger;

class ApiRetailCrmResponse_orders_edit extends ApiRetailCrmResponse
{
	public function __construct($source, array $args, $orderId)
	{
		$this->log = new Logger('edit order');
		parent::__construct($source);
		$this->log->push('orderData', json_decode($args['order'], true));
		$this->method = 'orders/' . $orderId . '/edit';
		$this->args = $args;
		$this->request('post');
	}
	public function getRemark()
	{
		return 'order (' . $this->response->id . ')';
	}
}
