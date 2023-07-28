<?

namespace php2steblya;

class TelegramBot_state_employee_personal_change_phone extends TelegramBot_state_employee_personal_change
{
	public function __construct($bot, $chat)
	{
		$this->personal = 'phone';
		parent::__construct($bot, $chat);
	}
}
