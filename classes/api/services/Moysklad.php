<?

namespace php2steblya\api\services;

use php2steblya\api\services\Moysklad_meta;

/**
 * Класс для работы с API МоегоСклада.
 * Наследует базовый функционал API-клиента и добавляет обработку специфичных для МоегоСклада ошибок.
 */
class Moysklad extends \php2steblya\api\ApiService
{
	/**
	 * Конструктор класса.
	 * Инициализирует базовый URL и заголовки для работы с API МоегоСклада.
	 */
	public function __construct(array $args = [])
	{
		$this->token = $_ENV['API_MOYSKLAD_TOKEN'];

		$args = [
			'baseUrl' => $_ENV['API_MOYSKLAD_SITE'],
			'headers' => [
				'Authorization' => 'Bearer ' . $_ENV['API_MOYSKLAD_TOKEN'], // Токен авторизации
				'Accept' => 'application/json;charset=utf-8', // Ожидаемый тип ответа
				'Accept-Encoding' => 'gzip', // Сжатие ответа
			]
		];

		parent::__construct($args);
	}

	/**
	 * Выполняет запрос к API MoySklad.
	 *
	 * Устанавливает заголовок `Content-Type` в зависимости от HTTP-метода.
	 * Подсталвяются массивы meta для тех сущностей, которые этого требуют .
	 *
	 * @param string $method HTTP-метод (GET, POST, PUT и т.д.).
	 * @param string $endpoint Конечная точка API.
	 * @param array $data Массив данных для отправки.
	 * @return object Ответ API.
	 */
	public function request(string $method, string $endpoint, array $data = []): object
	{
		if ($method === 'GET') {
			$this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
		} else {
			$this->setHeader('Content-Type', 'application/json');
			$data = Moysklad_meta::convert($data);
		}
		return parent::request($method, $endpoint, $data);
	}

	/**
	 * Обработка ответа от API МоегоСклада.
	 * Проверяет наличие ошибок в ответе и логирует их.
	 * @return object Проверенный ответ.
	 * @throws \Exception Если в ответе содержится ошибка.
	 */
	protected function handleErrorsInResponse(object $response): object
	{
		if (isset($response->errors)) {
			throw new \Exception($response->errors[0]->error);
		}
		return $response;
	}

	protected function prepareDataUrlencoded(array $data): string
	{
		return $this->buildQueryString($data);
	}

	/**
	 * Преобразует многомерный массив в строку query string для API МойСклад.
	 *
	 * @param array $data Массив параметров.
	 * @return string Готовая строка для передачи в запросе.
	 */
	private function buildQueryString(array $data, bool $isNested = false): string
	{
		$result = [];
		$delimiter = $isNested ? ';' : '&';

		foreach ($data as $key => $value) {
			if (!$value) continue;

			if (!is_array($value)) {
				$result[] = "{$key}={$value}";
				continue;
			}

			if (isset($value[0])) {
				foreach ($value as $val) {
					$result[] = "{$key}={$val}";
				}
				continue;
			}

			$result[] = "{$key}=" . $this->buildQueryString($value, true);
		}

		return implode($delimiter, array_filter($result));
	}
}
