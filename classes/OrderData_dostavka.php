<?

namespace php2steblya;

class OrderData_dostavka extends OrderData_dostavka_adres
{
	public $date;
	public $interval;
	public $cost;
	public $netCost;
	public $code;
	private bool $auto;

	public function __construct()
	{
		$this->auto = false;
		$this->cost = 500;
		$this->netCost = 0;
		$this->code = 'courier';
	}
	public function setDate($data)
	{
		$this->date = $data; //Y-m-d
	}
	public function setInterval($data)
	{
		$this->interval = $data;
	}
	public function setCode($data)
	{
		$this->code = $data;
	}
	public function setCost($data)
	{
		if (strlen($data) > 4) $data = 500;
		$this->cost = $data;
	}
	public function setNetCost($data)
	{
		$this->netCost = $data;
	}
	public function setAuto($items)
	{
		$autoFormats = ['коробка', 'корзинка', 'корзина', 'букет-гигант', 'корзинища', 'коробка XL', 'корзина XXL', 'корхина XXXL', 'корзина ГКЛЯТЬ ТАК ГУЛЯТЬ'];
		foreach ($items as $item) {
			foreach ($item->properties as $option) {
				if (in_array($option['option'], ['фор мат', 'Размер'])) continue;
				if (!in_array($option['variant'], $autoFormats)) break;
				$this->auto = true;
				return;
			}
		}
	}
	public function isAuto()
	{
		return $this->auto;
	}
}
