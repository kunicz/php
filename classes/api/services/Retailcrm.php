<?php

namespace php2steblya\api\services;

/**
 * Класс для работы с API RetailCRM.
 * Наследует базовый функционал API-клиента и добавляет специфику работы с RetailCRM.
 */
class Retailcrm extends \php2steblya\api\ApiService
{
	/**
	 * Конструктор класса.
	 * Инициализирует базовый URL для работы с API RetailCRM.
	 */
	public function __construct(array $args = [])
	{
		$this->token = $_ENV['API_RETAILCRM_TOKEN'];

		$args = [
			'baseUrl' => $_ENV['API_RETAILCRM_SITE'],
			'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']
		];

		parent::__construct($args);
	}

	/**
	 * Выполняет запрос к API RetailCRM.
	 * Автоматически добавляет ключ API (`apiKey`) в параметры запроса.
	 *
	 * @param string $method HTTP-метод (GET, POST, PUT и т.д.)
	 * @param string $endpoint Конечная точка API
	 * @param array $data Массив данных для отправки
	 * @return object
	 */
	public function request(string $method, string $endpoint, array $data = []): object
	{
		$data['apiKey'] = $_ENV['API_RETAILCRM_TOKEN'];
		return parent::request($method, $endpoint, $data);
	}

	/**
	 * Обрабатывает ответ от API RetailCRM.
	 * Логирует ошибки, если запрос не был успешным.
	 * @return object Проверенный ответ.
	 * @throws \Exception Если в ответе содержится ошибка.
	 */
	protected function handleErrorsInResponse(object $response): object
	{
		if (!$response->success) {
			$errorMsg = $response->errorMsg ?? 'Неизвестная ошибка';

			if (isset($response->errors) && is_object($response->errors)) {
				$errorsArray = [];
				foreach ($response->errors as $key => $value) {
					$errorsArray[] = $key . ': ' . (is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE));
				}
				$errorMsg .= ': ' . implode(', ', $errorsArray);
			}

			throw new \Exception('Ошибка в ответе от RetailCrm: ' . $errorMsg);
		}

		return $response;
	}
}
