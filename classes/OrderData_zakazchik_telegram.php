<?

namespace php2steblya;

class OrderData_zakazchik_telegram
{
	private $telegram;

	public function __construct($telegram)
	{
		$telegram = str_replace('https://t.me/', '', $telegram);
		$telegram = str_replace('@', '', $telegram);
		$telegram = strtolower($telegram);
		if (!preg_match('/^[a-z0-9_.-]+$/', $telegram)) {
			$this->telegram = '';
		} else {
			$this->telegram = $telegram;
		}
	}

	public function get()
	{
		return $this->telegram;
	}
}
