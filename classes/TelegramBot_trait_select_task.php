<?

namespace php2steblya;

use php2steblya\TelegramBot_keyboard as Keyboard;

trait TelegramBot_trait_select_task
{
	protected function select_task($job, $text = 'Какую задачу нужно выполнить?')
	{
		//если передан колбэк задачи для job
		foreach ($this->tasks[$job] as $btn) {
			if ($btn[1] != $this->chat->callback) continue;
			$this->state($this->chat->callback);
			return;
		}

		//если нужно вернуться к выбору job (select_job)
		if ('select_job' == $this->chat->callback) {
			$this->state('select_job');
			return;
		}

		$this->response[] = [
			'text' => $text,
			'reply_markup' => $this->select_task_menu_keyboard($this->tasks[$job])
		];
	}

	private function select_task_menu_keyboard($btns)
	{
		$keyboard = new Keyboard(Keyboard::collectButtons($btns));
		if (count($this->chat->user->jobs) > 1) $keyboard->addButtonRow(['Вернуться к выбору должности', 'select_job']);
		return $keyboard->getMarkup();
	}
}
