<?php

namespace php2steblya\db;

use php2steblya\Exception;
use PDO;

/**
 * Класс для подключения к базе данных (Singleton).
 */
class DbConnection
{
	/** @var PDO|null Экземпляр подключения к базе данных */
	private static ?PDO $pdo = null;

	/**
	 * Возвращает подключение к базе данных.
	 *
	 * Создает подключение, если оно еще не установлено (Singleton).
	 * 
	 * @throws Exception Если не удалось подключиться к базе данных.
	 */
	public static function getConnection(): PDO
	{
		if (self::$pdo === null) {
			try {
				self::$pdo = new PDO(
					'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'],
					$_ENV['DB_USERNAME'],
					$_ENV['DB_PASSWORD']
				);
				self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (\PDOException $e) {
				throw new \Exception('не удалось подключиться к базе данных: ' . $e->getMessage());
			}
		}

		return self::$pdo;
	}

	/**
	 * Возвращает список таблиц в базе данных.
	 *
	 * @return array Массив имен таблиц.
	 */
	public static function getTables(): array
	{
		$dbName = $_ENV['DB_DATABASE'];
		$sql = "SHOW TABLES FROM `$dbName`";
		$stmt = self::getConnection()->query($sql);
		$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_column($tables, array_keys($tables[0])[0]);
	}

	/**
	 * Извлекает имена столбцов таблицы.
	 *
	 * @param string $tableName Название таблицы.
	 * @return array Массив имён столбцов.
	 */
	public static function getColumns(string $tableName): array
	{
		$sql = "SHOW COLUMNS FROM $tableName";
		$stmt = self::getConnection()->query($sql);
		$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array_column($columns, 'Field');
	}
}
