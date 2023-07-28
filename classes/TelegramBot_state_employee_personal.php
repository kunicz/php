<?

namespace php2steblya;

use php2steblya\TelegramBot_keyboard as Keyboard;

class TelegramBot_state_employee_personal extends TelegramBot_state_employee
{
	protected function process()
	{
		switch ($this->chat->callback) {
			case 'personal_change_name':
			case 'personal_change_phone':
			case 'personal_change_bank':
			case 'select_' . $this->chat->state_comment . '_task':
				$this->state($this->chat->callback);
				return;
		}

		$this->response[] = [
			'text' => $this->personalData(),
			'reply_markup' => $this->personalKeybloard(),
			'parse_mode' => 'HTML'
		];
	}

	private function personalData()
	{
		$text = "Твои данные на данный момент:";
		$text .= "\n<b>Id: </b>" . $this->chat->id;
		$text .= "\n<b>Имя: </b>" . $this->chat->user->name;
		$text .= "\n<b>Должность: </b>" . implode(', ', array_column($this->chat->user->jobs, 'title'));
		$text .= "\n<b>Телефон: </b>" . $this->chat->user->phone;
		if ($this->chat->user->bank_requisite) {
			$text .= "\n<b>Оплата:</b>";
			$text .= "\nПеревод на " . (strlen($this->chat->user->bank_requisite) >= 12 ? "банковскую карту" : "номер телефона") . " " . $this->chat->user->bank_requisite;
			$text .= $this->chat->user->bank_title ? " (" . $this->chat->user->bank_title . ")" : "";
			$text .= $this->chat->user->bank_comment ? "\n" . $this->chat->user->bank_comment : "";
		} else {
			$text .= "\n<b>Оплата:</b> реквизиты не указаны";
		}
		return $text;
	}

	private function personalKeybloard()
	{
		$btns = [
			['Изменить имя', 'personal_change_name'],
			['Изменить телефон', 'personal_change_phone'],
			['Изменить реквизиты оплаты', 'personal_change_bank']
		];
		$keyboard = new Keyboard(Keyboard::collectButtons($btns));
		$keyboard->addButtonRow(['Вернуться к задачам', 'select_' . $this->chat->state_comment . '_task']);
		return $keyboard->getMarkup();
	}
}
