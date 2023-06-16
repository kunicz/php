<?

namespace php2steblya;

use php2steblya\Logger;

class ApiRetailCrmResponse_orders_get extends ApiRetailCrmResponse
{
	private array $ordersIds;

	public function __construct($source, array $args)
	{
		$this->log = new Logger('get orders');
		parent::__construct($source);
		$this->method = 'orders';
		$this->args = $args;
		$this->get();
		if (!$this->api->getCount()) {
			$this->log->pushNote('no orders found');
		}
		foreach ($this->api->response->orders as $order) {
			$this->ordersIds[] = $order->id;
		}
	}
	public function getOrders()
	{
		return $this->api->response->orders;
	}
	public function getOrdersIds(): array
	{
		return $this->ordersIds;
	}
	public function getRemark()
	{
		if (count($this->ordersIds) > 1) {
			return 'orders (' . implode(',', $this->ordersIds) . ')';
		} else {
			return 'order (' . $this->ordersIds[0] . ')';
		}
	}
}
