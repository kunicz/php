<?php

namespace php2steblya\api\modules\telegram;

use php2steblya\helpers\Validate;
use php2steblya\api\ApiModule;

/**
 * Модуль для работы с сообщениями в Telegram API.
 */
class Messages extends ApiModule
{
	/**
	 * Отправляет сообщение через Telegram API.
	 *
	 * @param array $data {
	 *     @type string $chat_id ID чата (обязательный).
	 *     @type string $text Текст сообщения. По умолчанию — пустая строка.
	 *     @type string $parse_mode (необязательный). Режим форматирования текста (HTML или Markdown).
	 *     @type array  $extra Дополнительные параметры для Telegram API.
	 * }
	 * @return object Ответ API Telegram.
	 */
	public function send(array $data): object
	{
		Validate::notEmpty($data['chat_id'], 'не передан id чата');
		Validate::notEmpty(trim($data['text']), 'не передан текст сообщения');

		return $this->request('POST', 'sendMessage', $data);
	}
}
