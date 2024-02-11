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
		$this->cost = 700;
		$this->netCost = 0;
		$this->code = 'courier';
	}
	public function setDate($data)
	{
		$this->date = $data; //Y-m-d
	}
	public function setInterval($data)
	{
		$string  = $data;
		$pattern = '/(с \d{2}:\d{2} до \d{2}:\d{2}) = \d+/';
		$replacement = '${1}';
		$this->interval = preg_replace($pattern, $replacement, $string);
	}
	public function setCode($data)
	{
		$this->code = $data;
	}
	public function setCost($data)
	{
		if (strlen($data) > 4) $data = $this->cost;
		$this->cost = $data;
	}
	public function setNetCost($data)
	{
		$this->netCost = $data;
	}
	public function setAuto($items)
	{
		$autoFormats = ['коробка', 'корзинка', 'корзина', 'букет-гигант', 'корзинища', 'коробка XL', 'корзина XXL', 'корзина XXXL', 'корзина ГКЛЯТЬ ТАК ГУЛЯТЬ'];
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
