<?

namespace php2steblya;

class OrderData_item
{
	public $sku;
	public $name;
	public int $initialPrice;
	public int $purchasePrice;
	public int $quantity;
	public $externalId;
	public array $properties;

	public function __construct(array $productFromTilda = null)
	{
		if (!$productFromTilda) return;
		$this->sku = $productFromTilda['sku'];
		$this->name = $productFromTilda['name'];
		$this->initialPrice = (int) $productFromTilda['amount'];
		$this->purchasePrice = (int) $productFromTilda['amount'];
		$this->quantity =	(int) $productFromTilda['quantity'];
		$this->externalId =	$productFromTilda['externalid'];
		$this->properties = $productFromTilda['options'];
	}
	public function getCrm(): array
	{
		return [
			'offer' => [
				'externalId' => (string) $this->externalId
			],
			'productName' => $this->name,
			'quantity' => $this->quantity,
			'initialPrice' => $this->initialPrice,
			'purchasePrice' => $this->purchasePrice,
			'properties' => $this->properties()
		];
	}
	private function properties(): array
	{
		$props = [];
		if (empty($this->properties)) return $props;
		foreach ($this->properties as $option) {
			$props[] = [
				'name' => $option['option'],
				'value' => $option['variant']
			];
		}
		if (substr($this->sku, -1) == 'v') { // если товар с витрины
			$props[] = [
				'name' => 'готов',
				'value' => 'на витрине'
			];
		}
		if ($this->initialPrice) {
			$props[] = [
				'name' => 'цена',
				'value' => $this->initialPrice
			];
		}
		return $props;
	}
	public function setScu($data)
	{
		$this->sku = $data;
	}
	public function setQuantity($data)
	{
		$this->quantity = $data;
	}
	public function setName($data)
	{
		$this->name = $data;
	}
	public function setPrice($data)
	{
		$this->initialPrice = $data;
		$this->purchasePrice = $data;
	}
	public function setInitialPrice($data)
	{
		$this->initialPrice = $data;
	}
	public function setPurchasePrice($data)
	{
		$this->purchasePrice = $data;
	}
	public function setExternalId($data)
	{
		$this->externalId = $data;
	}
	public function setProperties(array $data)
	{
		$this->properties = $data;
	}
}
