<?

namespace php2steblya;

class OrderData_item_sku
{
	private $sku;
	private bool $isVitrina;

	public function __construct($sku)
	{
		if (substr($sku, -1) == 'v') {
			$this->isVitrina = true;
			$this->sku = substr($sku, -1);
		} else {
			$this->isVitrina = false;
			$this->sku = $sku;
		}
	}
	public function isVitrina()
	{
		return $this->isVitrina;
	}
	public function get()
	{
		return $this->sku;
	}
}
