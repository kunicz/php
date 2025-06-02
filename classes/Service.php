<?php

namespace php2steblya;

use php2steblya\Script;
use php2steblya\interfaces\services\ServiceInterface;

// Абстрактный класс для работы с сервисами (API, база данных) напрямую через скрипт.
// Использует переданный в scriptData параметр 'request' для вызова модулей и их методов.
// Класс предназначен для обработки запросов, парсинга данных и вызова соответствующих методов модулей.
abstract class Service extends Script
{
	// Инициализация скрипта.
	// Проверяет корректность данных запроса, парсит 'request' в формате 'modulename/actionname',
	// вызывает соответствующий модуль и его метод, а затем возвращает результат.
	public function init(): void
	{
		// Проверяем, существует ли 'request' в scriptData
		if (empty($this->scriptData['request'])) {
			throw new \Exception("отсутствует 'request' в данных скрипта");
		}

		// Парсим request по маске {moduleName}/{actionName}
		$requestParts = explode('/', $this->scriptData['request']);
		if (count($requestParts) !== 2) {
			throw new \Exception("некорректный формат 'request': ожидается 'module/action'");
		}

		[$moduleName, $actionName] = $requestParts;

		// Дополнительная проверка: модули и действия не должны быть пустыми строками
		if (empty($moduleName) || empty($actionName)) {
			throw new \Exception("имя модуля или действия не может быть пустым");
		}

		// Без флага "PERMISSION_KEY" нельзя что-либо менять и получать деликатные данные
		if (!isset($this->scriptData[$_ENV['PERMISSION_KEY']])) {
			if (!$this->checkPermission(['moduleName' => $moduleName, 'actionName' => $actionName])) {
				throw new \Exception("недопустимый запрос $actionName");
			}
		}

		$this->logger->setGroup($moduleName . '/' . $actionName);

		// Инициализируем класс сервиса
		$service = $this->getService();
		// Инициализируем класс модуля
		$module = $service->{$moduleName}($this->scriptData);
		// Обращаемся к методу модуля
		$response = $module->{$actionName}($this->scriptData);

		$this->setResponse($response);
	}

	// Абстрактный метод для получения экземпляра сервиса.
	abstract protected function getService(): ServiceInterface;

	// Абстрактный метод для проверки прав доступа.
	// Выбрасывает исключение если без ключа доступа производится запрос к деликатным данным или изменению данных
	abstract protected function checkPermission(array $args): bool;
}
