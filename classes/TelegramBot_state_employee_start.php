<?

namespace php2steblya;

class TelegramBot_state_employee_start extends TelegramBot_state_employee
{
	protected function process()
	{
		//если юзер не сотрудник
		if (!$this->chat->user->is_employee) {
			$chatId = $this->chat->id;
			$this->response[] = [
				'text' => "Это бот только для сотрудников.\nК сожалению, бот не смог тебя найти в базе данных.\nЕсли ты сотрудник, обратись к администратору $this->replyTo и сообщи ему свой id: <b>$chatId</b>\nВремя работы менежеров: с 10 до 22",
				'parse_mode' => 'HTML'
			];
			return;
		}

		//если юзер сотрудник
		$this->state('select_job');
	}
}
