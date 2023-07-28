<?

namespace php2steblya;

class TelegramBot_state_employee_select_admin_task extends TelegramBot_state_employee
{
	use TelegramBot_trait_select_job;
	use TelegramBot_trait_select_task;

	protected function process()
	{
		if (!$this->chat->user->is_admin) {
			$this->select_job_no_premission('Администратор');
			return;
		}
		$this->chat->state_comment = 'admin';
		$this->select_task('admin');
	}
}
