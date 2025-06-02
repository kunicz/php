<?php

require_once __DIR__ . '/!autoload.php';

use php2steblya\Config;
use php2steblya\Script;
use php2steblya\Logger;
use php2steblya\ErrorHandler;
use php2steblya\ScriptFactory;

class Php2steblya
{
	// инициализация приложения
	public function init()
	{
		//константы и конфиги
		Config::init();

		//обработчик ошибок и исключений
		ErrorHandler::init();

		// определяем тип запроса, собираем аргументы и запускаем скрипт
		$requestType = $this->getRequestType();
		$scriptData = $this->getArgs($requestType);
		$this->run($requestType, $scriptData);
	}

	// определяем тип запроса (cron или http[webhook,ajax])
	private function getRequestType(): string
	{
		if (php_sapi_name() === 'cli') {
			return 'cron';
		} else {
			$uri = $_SERVER['REQUEST_URI'] ?? '/';
			return trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');
		}
	}

	// парсим аргументы
	private function getArgs(string $requestType): array
	{
		switch ($requestType) {
			case 'cron':
				global $argv;
				$args = [];
				foreach ($argv as $arg) {
					if (strpos($arg, '=') !== false) {
						[$name, $value] = explode('=', $arg, 2);
						$args[$name] = $value;
					} else {
						$args[$arg] = true; // Флаг без значения
					}
				}
				return $args;

			case 'ajax':
			case 'webhook':
				return $_GET;

			default:
				$this->redirectTo404();
		}
	}

	// Выполняет скрипт
	private function run(string $requestType, array $scriptData): void
	{
		set_time_limit(300); // 5 минут

		try {
			// initData вычленяется отдельно и используется только здесь внутри метода
			// для того чтобы правиьно сформировать лог
			$initData = [];
			foreach ($scriptData as $key => $value) {
				if (!in_array($key, ['script', 'request', 'logger'])) continue;
				$initData[$key] = $value;
			}

			// если не передан script, то дальнейшее выполнение скрипта не имеет смысла
			if (empty($initData['script'])) throw new \Exception('параметр (script) не передан');

			$logger = Logger::getInstance();
			$logger
				->addRoot('source', $requestType)
				->addRoot('init_data', $initData)
				->addRoot('time_start', date('Y-m-d H-i-s'));

			$scriptInstance = ScriptFactory::initClass($scriptData);

			$errorsCount = count($logger->getErrors());
			if ($errorsCount > 0) {
				$e = new \Exception("скрипт '{$initData['script']}' завершился с ошибками. Ошибок $errorsCount");
				Script::notifyAdmin($e);
			}

			Script::success($scriptInstance->getResponse(), isset($initData['logger']));
		} catch (\Throwable $e) {
			Script::fail($e);
		}
	}

	/** 
	 * Редирект на заглушку
	 * @return never
	 */
	private function redirectTo404(): void
	{
		header('Location: https://2steblya.ru/php');
		die();
	}
}


// Инициализация и запуск
$php2steblya = new Php2steblya();
$php2steblya->init();
