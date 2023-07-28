<?

namespace php2steblya;

trait TelegramBot_trait_hello
{
	/**
	 * здороваемся, если это новый чат или чат обновлялся 12 часов назад
	 */
	protected function hello($bot)
	{
		if ($this->chat->initial_state != 'start' && strtotime($this->chat->updated) < strtotime('-12 hours')) return;
		switch ($bot) {
			case 'employee':
				$hello = 'Привет';
				break;
			case '2steblya':
				$hello = 'кукусики';
				break;
			case 'stf':
				$hello = 'Здравствуйте';
				break;
		}
		$this->chat->initial_state = 'hello';
		//добавляем приветствие не в конец массива response, а в начало
		$this->response[] = ['text' => $hello . (isset($this->chat->user->name) ? ', ' . $this->chat->user->name : '')];
	}
}
