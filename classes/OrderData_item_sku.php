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
			$this->sku = substr($sku, 0, -1);
		} elseif (str_starts_with($sku, '777')) {
			$this->isVitrina = true;
			$this->sku = $sku;
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
