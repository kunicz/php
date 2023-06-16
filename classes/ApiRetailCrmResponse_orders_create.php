<?

namespace php2steblya;

class ApiRetailCrmResponse_orders_create extends ApiRetailCrmResponse
{
	public function __construct($source, array $args)
	{
		$this->log = new Logger('create order');
		parent::__construct($source);
		$this->log->push('orderData', json_decode($args['order'], true));
		$this->method = 'orders/create';
		$this->args = $args;
		$this->post();
	}
	public function getOrderId()
	{
		return $this->response->id;
	}
	public function getRemark()
	{
		return 'order (' . $this->response->id . ')';
	}
}
