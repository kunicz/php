<?php

namespace php2steblya\db;

use php2steblya\interfaces\services\FactoryInterface;

class DbFactory implements FactoryInterface
{
	/** @var DbService|null Единственный экземпляр DbService. */
	private static ?DbService $service = null;

	/**
	 * Возвращает экземпляр DbService.
	 * 
	 * Так как сервис для работы с базой данный у меня один (mySql), то он не нуждается в каком-то дополнительном определении
	 *
	 * @param string $serviceName Имя сервиса (пока только db).
	 * @param array $args Аргументы, передаваемые в конструктор сервиса (пока не используются).
	 * @return DbService
	 */
	public static function createService(string $serviceName = 'db', array $args = []): DbService
	{
		if (self::$service === null) {
			self::$service = new DbService();
		}
		return self::$service;
	}
}
