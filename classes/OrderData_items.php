<?

namespace php2steblya;

use php2steblya\OrderData_item as Item;

class OrderData_items
{
	private $site;
	private array $bukets;
	private array $items;

	public function fromTilda(array $productsFromTilda)
	{
		for ($i = 0; $i < count($productsFromTilda); $i++) {
			$item = new Item($productsFromTilda[$i]);
			if (!$i) $item->setPurchasePrice($this->decreaseFirstItemPrice($item->purchasePrice));
			$this->items[] = $item;
			$this->pushBuket($item);
		}
		$this->pushTransportItem();
	}
	public function getCrm(): array
	{
		$items = [];
		if (empty($this->items)) return $items;
		foreach ($this->items as $item) {
			$items[] = $item->getCrm();
		}
		return $items;
	}
	private function pushBuket($item)
	{
		if (empty($item->properties)) return;
		foreach ($item->properties as $option) {
			if (str_replace(' ', '', $option['option']) != 'формат') continue;
			$this->bukets[] = $item->name . ' - ' . $option['variant'] . ' (' . $item->quantity . ' шт)';
			break;
		}
	}
	public function getBukets(): string
	{
		if (empty($this->bukets)) return '';
		return implode(',', $this->bukets);
	}
	private function decreaseFirstItemPrice(int $price)
	{
		switch ($this->site) {
			case '2steblya':
				return $price - 1000; //транспортировочное(500) + доставка(500)
				break;
			case 'Stay True flowers':
				return $price - 700; //упаковка х 2(200) + доставка(500)
				break;
		}
	}
	private function pushTransportItem()
	{
		$item = new Item();
		switch ($this->site) {
			case '2steblya':
				$item->setPrice(500);
				$item->setName('Транспортировочное');
				$item->setExternalId(214);
				$item->setQuantity(1);
				break;
			case 'Stay True flowers':
				$item->setPrice(100);
				$item->setName('Упаковка');
				$item->setExternalId(223);
				$item->setQuantity(2);
				break;
		}
		$this->items[] = $item;
	}
	public function push($item)
	{
		$this->items[] = $item;
	}
	public function setSite($data)
	{
		$this->site = $data;
	}
}
