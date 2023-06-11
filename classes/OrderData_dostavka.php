<?

namespace php2steblya;

class OrderData_dostavka extends OrderData_dostavka_adres
{
	public $adress;
	public $date;
	public $interval;
	public $cost;
	public $netCost;
	public $type;
	private bool $auto;
	public function __construct()
	{
		$this->auto = false;
		$this->cost = 500;
		$this->type = 'Доставка курьером';
	}
	public function isAuto()
	{
		return $this->auto;
	}
	public function setDate($data)
	{
		$this->date = $data; //Y-m-d
	}
	public function setInterval($data)
	{
		$this->interval = $data;
	}
	public function setType($data)
	{
		$this->type = $data;
	}
	public function setCost($data)
	{
		$this->cost = $data;
	}
	public function setNetCost($data)
	{
		$this->netCost = $data;
	}
	public function setAuto($items)
	{
		$autoFormats = ['коробка', 'корзинка', 'корзина', 'букет-гигант', 'корзинища'];
		foreach ($items as $item) {
			foreach ($item->properties as $option) {
				if (str_replace(' ', '', $option['option']) != 'формат') continue;
				if (!in_array($option['variant'], $autoFormats)) break;
				$this->auto = true;
				return;
			}
		}
	}
}
