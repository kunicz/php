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
	public function fromTilda(array $productsFromTilda)
	{
		foreach ($productsFromTilda as $item) {
			$item = new Item($this->site, $item);
			$this->items[] = $item;
			$this->pushBuket($item);
			$this->pushCard($item);
		}
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
		//название
		$buket = $item->name;
		//формат
		foreach ($item->properties as $option) {
			if (!in_array($option['option'], ['фор мат', 'Размер'])) continue;
			$buket .= ' - ' . $option['variant'];
			break;
		}
		//свойства
		$dops = [];
		foreach ($item->properties as $option) {
			if (in_array($option['option'], ['артикул', 'цена', 'выебри карточку', 'выбери карточку', 'фор мат', 'Размер'])) continue;
			$dops[] = $option['option'] . ': ' . $option['variant'];
		}
		if (!empty($dops)) $buket .= ' (' . implode(', ', $dops) . ')';
		//количество
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
