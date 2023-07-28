<?

namespace php2steblya;

class TelegramBot_keyboard
{
	private array $keyboard;
	private array $markup;

	public function __construct(array $data)
	{
		$this->keyboard = $data;
	}

	/**
	 * определяем, это кнопка для инлайн или обычной клавиатуры
	 */
	private function button($btn)
	{
		return is_array($btn) ? ['text' => $btn[0], 'callback_data' => $btn[1]] : $btn;
	}

	/**
	 * формируем из массива кнопок четкие ряды по $btnsInRow кнопок в ряде
	 */
	public static function collectButtons($btns, $btnsInRow = 2): array
	{
		$keyboard = [];
		$row = [];
		foreach ($btns as $btn) {
			$row[] = self::button($btn);
			if (count($row) === $btnsInRow) {
				$keyboard[] = $row;
				$row = [];
			}
		}
		if (count($row) > 0) {
			$keyboard[] = $row;
		}
		return $keyboard;
	}

	/**
	 * добавить кнопку в последний ряд
	 */
	public function addButton($data)
	{
		$this->keyboard[count($this->keyboard) - 1][] = $this->button($data);
	}

	/**
	 * добавить кнопку в новый ряд
	 */
	public function addButtonRow($data)
	{
		$this->keyboard[][] = $this->button($data);
	}

	private function isInlineKeyboard()
	{
		if (is_array($this->keyboard[0][0])) return true;
		return false;
	}

	public function getMarkup()
	{
		if ($this->isInlineKeyboard()) {
			$this->markup['inline_keyboard'] = $this->keyboard;
		} else {
			$this->markup['resize_keyboard'] = true;
			$this->markup['one_time_keyboard'] = true;
			$this->markup['keyboard'] = $this->keyboard;
		}
		return json_encode($this->markup);
	}
}
