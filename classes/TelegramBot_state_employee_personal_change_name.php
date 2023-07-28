<?

namespace php2steblya;

class TelegramBot_state_employee_personal_change_name extends TelegramBot_state_employee_personal_change
{
	public function __construct($bot, $chat)
	{
		$this->personal = 'name';
		parent::__construct($bot, $chat);
	}
}
