<?

namespace php2steblya;

use php2steblya\TelegramBot_keyboard as Keyboard;

trait TelegramBot_trait_select_job
{
	/**
	 * определяем дальнейшие действия по message или по callback
	 */
	protected function select_job_by_input()
	{
		foreach ($this->chat->user->jobs as $job) {
			if ($this->message == $job['title'] || $this->chat->callback == $job['id']) {
				$this->state('select_' . $job['id'] . '_task');
				return true;
			}
		}
		return false;
	}

	protected function select_job_menu()
	{
		$this->response[] = [
			'text' => 'Работа с какой должностью тебя сейчас интересует?',
			'reply_markup' => $this->select_job_menu_keyboard()
		];
	}

	protected function select_job_menu_keyboard()
	{
		$btns = [];
		foreach ($this->chat->user->jobs as $job) {
			$btns[] = [$job['title'], $job['id']];
		}
		$keyboard = new Keyboard(Keyboard::collectButtons($btns));
		return $keyboard->getMarkup();
	}

	protected function select_job_no_premission($jobTitle)
	{
		$this->response[] = [
			'text' => "У тебя нет доступа к уровню <b>$jobTitle</b>\nВыбери ту должность, к которой у тебя есть доступ:",
			'reply_markup' => $this->select_job_menu_keyboard(),
			'parse_mode' => 'HTML'
		];
		$this->chat->state = 'select_job';
	}
}
