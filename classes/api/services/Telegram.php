<?php

namespace php2steblya\api\services;

use php2steblya\db\Db;
use php2steblya\Logger;

class Telegram extends \php2steblya\api\ApiService
{

	/**
	 * Конструктор Telegram API-клиента.
	 */
	public function __construct(array $args = [])
	{
		$args = ['headers' => ['Content-Type' => 'application/json']];
		parent::__construct($args); // Базовый URL пока пустой, так как он устанавливается через setBotName.
	}

	/**
	 * Устанавливает имя бота и инициализирует необходимые данные.
	 *
	 * @param string $botName Имя бота.
	 * @return self
	 * @throws \Exception Если бот с указанным именем не найден.
	 */
	public function setBotName(string $botName): self
	{
		Logger::getInstance()->setSubGroup('Script :: admin_telegram_bot');

		$args = [
			'fields' => ['telegram_id', 'token'],
			'where' => ['name' => $botName],
			'limit' => 1
		];
		$apiResponse = Db::createService()->telegram_bots()->get($args);
		if (empty($apiResponse)) {
			throw new \Exception("бот с именем $botName не найден");
		}

		$botId = $apiResponse['telegram_id'];
		$botToken = $apiResponse['token'];
		$this->token = $botToken;
		$this->baseUrl = $_ENV['API_TELEGRAM_SITE'] . '/bot' . $botId . ':' . $botToken;

		Logger::getInstance()->exitSubGroup();

		return $this;
	}

	/**
	 * Обработка ответа от Telegram API.
	 *
	 * @param object $response Ответ API.
	 * @return object Проверенный ответ.
	 * @throws Exception Если в ответе содержится ошибка.
	 */
	protected function handleErrorsInResponse(object $response): object
	{
		if (!isset($response->ok) || !$response->ok) {
			throw new \Exception('Ошибка Telegram API: ' . ($response->description ?? 'Неизвестная ошибка'));
		}
		return $response;
	}
}
