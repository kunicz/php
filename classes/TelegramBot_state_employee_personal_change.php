<?

namespace php2steblya;

use php2steblya\TelegramBot_keyboard as Keyboard;

abstract class TelegramBot_state_employee_personal_change extends TelegramBot_state_employee
{
	protected $personal;
	private array $personalData;

	public function __construct($bot, $chat)
	{
		parent::__construct($bot, $chat);
		$this->personalData = [
			'name' => $this->chat->user->name,
			'phone' => $this->chat->user->phone,
			'bank' => $this->chat->user->bank_requisite . ($this->chat->user->bank_title ? ' (' . $this->chat->user->bank_title . ($this->chat->user->bank_comment ? ' / ' . $this->chat->user->bank_comment : '') . ')' : '')
		];
	}

	public function process()
	{
		if ($this->chat->callback == 'personal') {
			$this->state($this->chat->callback);
			return;
		}

		if ($this->message) {
			$this->response[] = ['text' => $this->message];
			return;
		}

		$this->response[] = [
			'text' => "Текущее значение: <b>" . $this->personalData[$this->personal] . "</b>\nУкажи новое значение",
			'parse_mode' => 'HTML',
			'reply_markup' => $this->personal_change_menu_keyboard()
		];
	}

	private function personal_change_menu_keyboard()
	{
		$btns = [['Назад', 'personal']];
		$keyboard = new Keyboard(Keyboard::collectButtons($btns));
		return $keyboard->getMarkup();
	}
}
