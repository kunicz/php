<?

namespace php2steblya;

use php2steblya\OrderData_item as Item;

class OrderData_items
{
	private $site;
	public array $items;
	private array $cards;
	private array $bukets;

	public function __construct($site)
	{
		$this->site = $site;
		$this->items = [];
	}
	public function pushTransportItem()
	{
		/**
		 * автоматически добавляем транспортировочное, если товары в заказе есть и это не определенные товары
		 */
		if (empty($this->items)) return;
		if (in_array($this->items[0]->name, castrated_items())) return;
		$this->items[0]->setTransposrPrice($this->site);
		$item = new Item($this->site);
		switch ($this->site) {
			case $_ENV['site_2steblya_id']:
				$item->setPrice(500);
				$item->setName('Транспортировочное');
				$item->setQuantity(1);
				break;
			case $_ENV['site_stf_id']:
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
	public function get()
	{
		return $this->items;
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
			if (in_array($item->name, castrated_items())) continue;
			if (!in_array($option['option'], ['выебри карточку', 'выбери карточку'])) continue;
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
		//name
		$buket = $item->name;
		//format
		foreach ($item->properties as $option) {
			if (str_replace(' ', '', $option['option']) != 'формат') continue;
			$buket .= ' - ' . $option['variant'];
			break;
		}
		//additional props
		$dops = [];
		foreach ($item->properties as $option) {
			if (in_array($option['option'], ['артикул', 'цена', 'выебри карточку', 'выбери карточку', 'фор мат', 'формат'])) continue;
			$dops[] = str_replace('?', '', $option['option']) . ': ' . $option['variant'];
		}
		if (!empty($dops)) $buket .= ' (' . implode(', ', $dops) . ')';
		//quantity
		$buket .= ' (' . $item->quantity . ' шт)';
		$this->bukets[] = $buket;
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
