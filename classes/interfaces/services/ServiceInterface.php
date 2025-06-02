<?php

namespace php2steblya\interfaces\services;

use Exception;

interface ServiceInterface
{
	/**
	 * Возвращает модуль по его имени.
	 *
	 * Этот метод позволяет динамически получать модули, связанные с сервисом.
	 * Имя модуля преобразуется в название класса, который должен находиться
	 * в соответствующем пространстве имён.
	 *
	 * Пример реализации:
	 * 
	 * $moduleName = ucfirst($moduleName);
	 * $className = __NAMESPACE__ . '\\modules\\' . strtolower($this->serviceName) . '\\' . $moduleName;
	 *
	 * if (!class_exists($className)) {
	 *     throw new Exception("Модуль $moduleName не найден");
	 * }
	 *
	 * return new $className($this);
	 *
	 * @param string $moduleName Имя модуля, который необходимо получить.
	 * @param array $args Дополнительные аргументы, которые могут быть переданы в конструктор модуля.
	 * @return ModuleInterface Возвращает объект модуля, реализующий интерфейс ModuleInterface.
	 * @throws \Exception Если модуль с указанным именем не найден.
	 */
	public function __call(string $moduleName, array $args): ModuleInterface;
}
