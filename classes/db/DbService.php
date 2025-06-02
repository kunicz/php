<?php

namespace php2steblya\db;

use php2steblya\helpers\StringCase;
use php2steblya\db\tables\Any as DbTableDefault;
use php2steblya\interfaces\services\ServiceInterface;
use php2steblya\db\DbTable;

/** Динамическая работа с таблицами базы данных. */
class DbService implements ServiceInterface
{
	public function __construct()
	{
		//так как сервис DB один ндинственный, никаких настроек и логики нет 
	}

	/**
	 * Создает (модуль) экземпляр класса таблицы в базе данных.
	 *
	 * @param string $tableName Имя таблицы.
	 * @param array $sqlArgs Аргумент (по которому надо будет строить фильтры) для конструктора.
	 * @throws Exception Если модуль не найден.
	 */
	public function __call(string $tableName, array $sqlArgs = []): DbTable
	{
		$allowedTables = DbConnection::getTables();
		if (!in_array($tableName, $allowedTables)) {
			throw new \Exception("недоступное имя таблицы $tableName");
		}

		$className = __NAMESPACE__ . '\\tables\\' . StringCase::pascal($tableName);

		return class_exists($className) ? new $className($tableName, $sqlArgs) : new DbTableDefault($tableName, $sqlArgs);
	}
}
