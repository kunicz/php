<?

namespace php2steblya;

use php2steblya\File;

class OrderData_item
{
	public $sku;
	public $name;
	private $site;
	public int $initialPrice; //цена продажи
	public int $purchasePrice; //цена закупки
	public int $transportPrice; //цена продажи измененная (в функции pushTransport)
	public int $quantity;
	public array $properties;

	public function __construct(string $site, array $productFromTilda = null)
	{
		$this->site = $site;
		if (!$productFromTilda) return;
		$this->sku = $productFromTilda['sku'];
		$this->name = $productFromTilda['name'];
		$this->initialPrice = (int) $productFromTilda['price'];
		$this->transportPrice = (int) $productFromTilda['price'];
		$this->purchasePrice = (int) $productFromTilda['price'];
		$this->quantity = (int) $productFromTilda['quantity'];
		$this->properties = $productFromTilda['options'];
	}
	public function getCrm(): array
	{
		$item = [
			'offer' => $this->getOffer(),
			'productName' => $this->name,
			'quantity' => $this->quantity,
			'initialPrice' => $this->transportPrice,
			'purchasePrice' => $this->purchasePrice,
			'properties' => $this->properties()
		];
		return $item;
	}
	private function getOffer(): array
	{
		$offerData = [];
		switch ($this->name) {
			case 'Транспортировочное':
				$offerData['id'] = 1249;
				$offerData['externalId'] = '214';
				break;
			case 'Упаковка':
				$offerData['id'] = 1258;
				$offerData['externalId'] =  '223';
				break;
		}
		if (!empty($offerData)) return $offerData;
		$catalog = new File(dirname(dirname(__FILE__)) . '/TildaYmlCatalog_' . $this->site . '.txt');
		$catalog = json_decode($catalog->getContents(), true);
		foreach ($catalog['offers'] as $offer) {
			if ($offer['vendorCode'] == $this->sku) $offerData['externalId'] = $offer['id'];
		}
		return $offerData;
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
	public function setTransposrPrice($site)
	{
		switch ($site) {
			case '2steblya':
				$this->transportPrice = $this->initialPrice - (1000 / $this->quantity); //транспортировочное(500) + доставка(500)
				break;
			case 'Stay True flowers':
				$this->transportPrice = $this->initialPrice - (700 / $this->quantity); //упаковка х 2(200) + доставка(500)
				break;
		}
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
		$this->transportPrice = $data;
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
}
