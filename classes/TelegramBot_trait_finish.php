<?

namespace php2steblya;

trait TelegramBot_trait_finish
{
	protected function finish($bot)
	{
		if ($this->message != 'finish') return;
		switch ($bot) {
			case 'employee':
				if (in_array($this->chat->state, ['start', 'select_job', 'select_admin', 'select_manager', 'select_florist', 'select_courier'])) {
					$text = "Можно было бы и не дергать бота.\nВозвращайся, когда созреешь";
				} else {
					$text = "Надеюсь, бот был тебе полезен.\nУдачного дня";
				}
				break;
			case '2steblya':
			case 'stf':
		}
		$this->response = [['text' => $text]];
		$this->chat->state = 'start';
		$this->chat->messages = [];
	}
}
