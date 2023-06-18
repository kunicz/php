<?

namespace php2steblya;

use php2steblya\Logger;

class ApiRetailCrmResponse_products_get extends ApiRetailCrmResponse
{
	private array $ids;
	private $names;

	public function __construct($source, array $args)
	{
		$this->log = new Logger('get products');
		parent::__construct($source);
		$this->method = 'store/products';
		$this->args = $args;
		$this->request('get');
		if (!$this->api->getCount()) {
			$this->log->pushNote('no products found');
		}
		foreach ($this->api->response->products as $product) {
			$this->ids[] = $product->id;
			$this->names[] = preg_replace('/\s-\s.*?$/', '', $product->name);
		}
	}
	public function has()
	{
		return $this->api->getCount() ? true : false;
	}
	public function get()
	{
		return $this->api->response->products;
	}
	public function getIds(): array
	{
		return $this->ids;
	}
	public function getNames()
	{
		return $this->names;
	}
	public function getRemark()
	{
		if (count($this->ids) > 1) {
			return 'products (' . implode(',', $this->ids) . ')';
		} else {
			return 'product (' . $this->ids[0] . ')';
		}
	}
}
