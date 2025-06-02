<?php

namespace php2steblya\api;

use php2steblya\Logger;
use php2steblya\Exception;
use php2steblya\api\ApiModule;
use php2steblya\api\ApiResponseHandler;
use php2steblya\interfaces\services\ServiceInterface;

/**
 * Абстрактный класс для работы с API.
 * 
 * Предоставляет базовые методы для выполнения HTTP-запросов и обработки ответов.
 * @method retailcrm
 * @method moysklad
 * @method telegram
 */
abstract class ApiService implements ServiceInterface
{
	/** @var string Базовый URL для API, к которому выполняются запросы. */
	protected string $baseUrl;

	/** @var array Ассоциативный массив заголовков для HTTP-запросов. */
	protected array $headers;

	/** @var mixed Токен для авторизации, если требуется (например, Bearer-токен). */
	protected $token = '';

	/** @var string Имя вызываемого api-сервиса (например, retailcrm, telegram) */
	protected string $serviceName;

	/** @var Logger Логгер */
	protected Logger $logger;

	/**
	 * Конструктор API-клиента.
	 * 
	 * @param string $baseUrl Базовый URL для API (например, 'https://api.example.com').
	 * @param array $headers Ассоциативный массив заголовков для запросов.
	 */
	public function __construct(array $args = [])
	{
		$this->logger = Logger::getInstance();
		$this->baseUrl = rtrim($args['baseUrl'] ?? '', '/');
		$this->headers = $args['headers'] ?? [];
		$this->serviceName = $this->getServiceName();
	}

	/**
	 * Магический метод для инициализации экземпляров классов модулей (Customers, Orders etc)
	 * 
	 * @param string $moduleName Имя модуля
	 * @param array $moduleArgs массив аргументов, который не используется
	 */
	public function __call(string $moduleName, array $moduleArgs = []): ApiModule
	{
		$moduleName = ucfirst($moduleName);
		$className = __NAMESPACE__ . '\\modules\\' . strtolower($this->serviceName) . '\\' . $moduleName;

		if (!class_exists($className)) {
			throw new \Exception("модуль $moduleName не найден");
		}

		return new $className($this);
	}

	/**
	 * Выполняет HTTP-запрос к API.
	 * Вызывается из дочерних классов в переопределенном методе через parent::request()
	 *
	 * @param string $method HTTP-метод запроса (например, 'GET', 'POST', 'PUT', 'DELETE').
	 * @param string $endpoint Конечная точка API (например, '/users' или '/items').
	 * @param array $data Данные для передачи в запросе (например, тело запроса или параметры URL).
	 * @return object Ответ API в виде объекта.
	 * @throws Exception Если произошла ошибка выполнения запроса или обработки ответа.
	 */
	public function request(string $method, string $endpoint, array $data = []): object
	{
		if (empty($this->baseUrl)) {
			throw new \Exception("базовый URL не установлен. Убедитесь, что был вызван метод, например, setBotName()");
		}

		$url = $this->baseUrl . '/' . ltrim($endpoint, '/');
		$ch = null;

		$this->logger
			->setSubGroup(strtolower(implode('_', ['api', $this->serviceName, $endpoint, $method])))
			->setSubGroup('request')
			->add('method', $method)
			->add('url', $this->hideToken($url))
			->add('data', $this->hideToken($data))
			->add('headers', $this->hideToken($this->formatHeaders()));

		try {
			$ch = $this->initializeCurl($method, $url, $data);
			$response = curl_exec($ch);
			if ($response === false) {
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$errorMessage = $httpCode ? "http код: $httpCode" : curl_error($ch);
				throw new \Exception($errorMessage);
			}

			$this->logger
				->exitSubGroup()
				->setSubGroup('response')
				->add('raw', json_encode($response));

			$response = ApiResponseHandler::decode($response);
			$response = ApiResponseHandler::ensureObject($response);
			$response = ApiResponseHandler::check($response);
			$response = $this->handleErrorsInResponse($response);

			$this->logger
				->add('obj', $response)
				->exitSubGroup()
				->exitSubGroup();

			return $response;
		} finally {
			if ($ch) curl_close($ch);
		}
	}

	/**
	 * Инициализирует и настраивает cURL для выполнения HTTP-запроса.
	 *
	 * @param string $method HTTP-метод запроса (например, 'GET', 'POST', 'PUT', 'DELETE').
	 * @param string $url Полный URL запроса.
	 * @param array $data Данные для передачи в запросе.
	 * @return \CurlHandle Инициализированный cURL-объект.
	 * @throws Exception Если используется неподдерживаемый HTTP-метод.
	 */
	private function initializeCurl(string $method, string $url, array $data): \CurlHandle
	{
		$ch = curl_init();
		if ($ch === false) {
			throw new \Exception('не удалось инициализировать cURL');
		}

		$options = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => strtoupper($method),
			CURLOPT_URL            => $url,
			CURLOPT_HTTPHEADER     => $this->formatHeaders(),
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
		];

		$data = $this->prepareDataAccodingToContentType($data);
		$this->logger->add('data_raw', $this->hideToken($data));

		if (in_array(strtoupper($method), ['GET', 'DELETE'])) {
			$url .= '?' . $data;
			$options[CURLOPT_URL] = $url;
		} else {
			$options[CURLOPT_POSTFIELDS] = $data;
		}

		curl_setopt_array($ch, $options);
		return $ch;
	}

	/**
	 * Преобразует данные в соответствующий формат в зависимости от типа контента.
	 *
	 * @param mixed $data Данные для преобразования (массив, объект, строка или число).
	 * @return string Преобразованные данные.
	 * @throws Exception Если тип контента не поддерживается.
	 */
	private function prepareDataAccodingToContentType($data): string
	{
		$contentType = $this->headers['Content-Type'] ?? 'application/json';

		return match ($contentType) {
			'application/json' => $this->prepareDataJson($data),
			'application/x-www-form-urlencoded' => $this->prepareDataUrlencoded($data),
			default => throw new \Exception("неподдерживаемый тип контента: $contentType"),
		};
	}

	/**
	 * Преобразует данные в JSON-строку для отправки в API сервиса.
	 *
	 * @param array $data Данные для кодирования.
	 * @return string JSON-строка.
	 */
	protected function prepareDataJson(array $data): string
	{
		return json_encode($data);
	}

	/**
	 * Преобразует данные в строку формата application/x-www-form-urlencoded для отправки в API сервиса.
	 *
	 * @param array $data Данные для кодирования.
	 * @return string Кодированная строка.
	 */
	protected function prepareDataUrlencoded(array $data): string
	{
		return http_build_query($data);
	}

	/**
	 * Форматирует заголовки для использования в cURL-запросе.
	 *
	 * @return array Массив заголовков в формате ['Key: Value'].
	 */
	private function formatHeaders(): array
	{
		return array_map(
			fn($key, $value) => "$key: $value",
			array_keys($this->headers),
			$this->headers
		);
	}

	/**
	 * Устанавливает заголовок для запроса к API сервиса.
	 *
	 * @param string $key Ключ заголовка.
	 * @param string $value Значение заголовка.
	 * @return void
	 */

	public function setHeader(string $key, string $value): void
	{
		$this->headers[$key] = $value;
	}

	/**
	 * Заменяет токен на строку-заглушку в логируемых данных.
	 *
	 * @param mixed $input Данные для обработки (строка, массив или другие типы).
	 * @return mixed Обработанные данные с заменённым токеном.
	 */
	private function hideToken(mixed $input): mixed
	{
		if (is_array($input)) {
			// Рекурсивно обрабатываем массив
			foreach ($input as $key => $value) {
				$input[$key] = $this->hideToken($value);
			}
			return $input;
		}

		if (is_string($input)) {
			return $this->replaceTokenInString($input);
		}

		// Если тип не поддерживается, возвращаем значение без изменений
		return $input;
	}

	/**
	 * Заменяет токен на строку-заглушку в переданной строке.
	 *
	 * @param string $string Исходная строка.
	 * @return string Строка с заменённым токеном.
	 */
	private function replaceTokenInString(string $string): string
	{
		if (!$this->token) return $string;
		return str_replace($this->token, '<api_token>', $string);
	}

	/**
	 * Возвращает имя сервиса (например, retailcrm, moysklad, telegram)
	 * 
	 * @return string Имя сервиса
	 */
	private function getServiceName(): string
	{
		$namespaceParts = explode('\\', get_called_class());
		return end($namespaceParts);
	}

	/**
	 * Обрабатывает ошибки в ответе API.
	 *
	 * @param object $response Ответ API.
	 * @return object Проверенный и обработанный ответ.
	 * @throws \Exception Если в ответе обнаружены ошибки.
	 */
	abstract protected function handleErrorsInResponse(object $response): object;
}
