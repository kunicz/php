<?

namespace php2steblya;

use php2steblya\OrderData_item as Item;

class OrderData_items
{
	private $site;
	private array $cards;
	private array $bukets;
	private array $items;

	public function __construct($site)
	{
		$this->site = $site;
		$this->items = [];
	}
	public function pushTransportItem()
	{
		if (!empty($this->items)) $this->items[0]->setTransposrPrice($this->site);
		$item = new Item($this->site);
		switch ($this->site) {
			case '2steblya':
				$item->setPrice(500);
				$item->setName('Транспортировочное');
				$item->setQuantity(1);
				break;
			case 'staytrueflowers':
				$item->setPrice(100);
				$item->setName('Упаковка');
				$item->setQuantity(2);
				break;
		}
		$this->items[] = $item;
	}
	public function fromTilda(array $productsFromTilda)
	{
		foreach ($productsFromTilda as $item) {
			$item = new Item($this->site, $item);
			$this->items[] = $item;
			$this->pushBuket($item);
			$this->pushCard($item);
		}
		$this->pushTransportItem();
	}
	public function getCrm(): array
	{
		$items = [];
		foreach ($this->items as $item) {
			$items[] = $item->getCrm();
		}
		return $items;
	}
	private function pushCard($item)
	{
		if (empty($item->properties)) return;
		foreach ($item->properties as $option) {
			if ($option['option'] != 'выебри карточку') continue;
			$this->cards[] = $option['variant'];
			break;
		}
	}
	public function getCards()
	{
		if (empty($this->cards)) return '';
		return implode(', ', $this->cards);
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
		return implode(', ', $this->bukets);
	}
	public function push($item)
	{
		$this->items[] = $item;
	}
}
