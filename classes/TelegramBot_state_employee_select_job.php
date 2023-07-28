<?

namespace php2steblya;

class TelegramBot_state_employee_select_job extends TelegramBot_state_employee
{
	use TelegramBot_trait_select_job;

	protected function process()
	{
		if ($this->select_job_by_input()) return;
		if (count($this->chat->user->jobs) > 1) {
			$this->select_job_menu();
		} else {
			$this->state('select_' . $this->chat->user->jobs[0]['id'] . '_tasks');
		}
	}
}
