<?php

namespace php2steblya\api;

use php2steblya\helpers\StringCase;
use php2steblya\interfaces\services\FactoryInterface;

/**
 * Фабрика для создания сервисов API.
 */
class ApiFactory implements FactoryInterface
{
	/**
	 * Создаёт экземпляр указанного сервиса API.
	 *
	 * @param string $serviceName Имя сервиса (например, 'retailcrm', 'moysklad', 'telegram').
	 * @param array $args Аргументы, передаваемые в конструктор сервиса.
	 * @return ApiService Экземпляр сервиса API.
	 * @throws \Exception Если указанный сервис не найден.
	 */
	public static function createService(string $serviceName, array $args = []): ApiService
	{
		$className = __NAMESPACE__ . '\\services\\' . StringCase::pascal($serviceName);

		if (!class_exists($className)) {
			throw new \Exception("сервис api '$serviceName' не найден");
		}

		return new $className($args);
	}
}
