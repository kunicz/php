<?php

namespace php2steblya\interfaces\services;

use php2steblya\interfaces\services\ServiceInterface;

/**
 * Интерфейс для фабрики сервисов.
 *
 * Определяет метод для создания экземпляров сервисов на основе имени сервиса и аргументов.
 */
interface FactoryInterface
{
	/**
	 * Создаёт экземпляр сервиса.
	 *
	 * Этот метод должен быть реализован в классах, реализующих данный интерфейс,
	 * чтобы динамически создавать экземпляры сервисов на основе их имени и переданных аргументов.
	 *
	 * @param string $serviceName Имя сервиса, который необходимо создать.
	 * @param array $args Аргументы, передаваемые в конструктор сервиса.
	 * @return ServiceInterface Возвращает экземпляр сервиса, реализующий интерфейс ServiceInterface.
	 * @throws \Exception Если сервис с указанным именем не найден или не может быть создан.
	 */
	public static function createService(string $serviceName, array $args): ServiceInterface;
}
