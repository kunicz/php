<?

namespace php2steblya\api\services;

/**
 * Класс для работы с API Тильды.
 * Наследует базовый функционал API-клиента и добавляет обработку специфичных для Тильды ошибок.
 */
class Tilda extends \php2steblya\api\ApiService
{
	/**
	 * Конструктор класса.
	 * Инициализирует базовый URL и заголовки для работы с API.
	 */
	public function __construct(array $args = [])
	{
		$args = [
			'baseUrl' => $_ENV['API_TILDA_SITE'],
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded'
			]
		];

		parent::__construct($args);
	}

	/**
	 * Выполняет запрос к API.
	 * Автоматически добавляет ключи в параметры запроса.
	 *
	 * @param string $method HTTP-метод (GET, POST, PUT и т.д.)
	 * @param string $endpoint Конечная точка API
	 * @param array $data Массив данных для отправки
	 * @return object
	 */
	public function request(string $method, string $endpoint, array $data = []): object
	{
		$data['publickey'] = $_ENV['API_TILDA_PUBLIC_KEY'];
		$data['secretkey'] = $_ENV['API_TILDA_SECRET_KEY'];
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
		if ($response->status === 'ERROR') {
			throw new \Exception('Tilda response error: ' . $response->message);
		}
		return $response;
	}
}
