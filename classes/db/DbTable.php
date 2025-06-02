<?php

namespace php2steblya\db;

use PDO;
use php2steblya\Logger;
use php2steblya\interfaces\services\ModuleInterface;
use php2steblya\db\actions\Get;
use php2steblya\db\actions\Exist;
use php2steblya\db\actions\Insert;
use php2steblya\db\actions\Update;
use php2steblya\db\actions\Upsert;
use php2steblya\db\actions\Delete;

// Базовый класс для работы с таблицами базы данных.
abstract class DbTable implements ModuleInterface
{

	protected PDO $db; // Подключение к базе данных
	protected string $tableName; // Название таблицы
	protected array $tableColumns; // Массив колонок таблицы
	protected array $tableSqlArgs; // Массив данных для построения sql-query на уровне класса
	protected Logger $logger;

	// Конструктор таблицы.
	// Инициализирует подключение к базе данных, имя таблицы, её колонки и фильтры.
	public function __construct(string $tableName, array $tableSqlArgs = [])
	{
		$this->logger = Logger::getInstance();
		$this->db = DbConnection::getConnection();
		$this->tableName = $tableName;
		$this->tableColumns = DbConnection::getColumns($this->tableName);
		$this->tableSqlArgs = $tableSqlArgs;
	}

	// Возвращает имя таблицы, основанное на имени класса.
	public function getTableName(): string
	{
		return $this->tableName;
	}

	// Получает массив аргументов для построения sql-query, отбирая лишь те, которые должны задаваться на уровне класса.
	// Необходим для корректной работы скрипта \scripts\Db в строке $response = $this->db->{$moduleName}($queryCauses)->{$actionName}($queryCauses);
	// Для каждого экземпляра класса реализуется своя логика или можно унаследоваться от класса \db\tables\Any, в котором метод реалзован и всегда возвращает []
	abstract protected function tableSqlArgs(array $data = []): array;

	// возвращает аргументы переданный в crud-метод для дальнейшего слияния с аргументкат класса
	abstract protected function methodSqlArgs(array $data = []): array;

	// Сливает данные, переданные в метод с данными, сохраненными в свойстве tableQuery,
	// и возвращает строковые выражения для SQL.
	public function getSqlArgs(array $args = []): array
	{
		$this->logger->setSubGroup(implode('_', ['db', $this->tableName, $args['method']]));
		$this->logger->setSubGroup('request');

		// Обратаываем параметры на уровне таблицы
		$tableSqlArgs = $this->tableSqlArgs(...$this->tableSqlArgs);

		// Удаляем параметры, которые были нужны для таблицы 
		$methodSqlArgs = $this->methodSqlArgs($args);

		// Слияние параметров с логикой замены и дополнения
		$sqlArgsMerged = $this->mergeSqlArgs($tableSqlArgs, $methodSqlArgs);

		// Формируем данные для SQL
		$sqlArgsPrepared = [
			'fields' => !empty($sqlArgsMerged['fields']) ? implode(', ', $sqlArgsMerged['fields']) : '*',
			'join' => $sqlArgsMerged['join'] ?? [],
			'where' => $sqlArgsMerged['where'] ?? [],
			'set' => $sqlArgsMerged['set'] ?? [],
			'limit' => $sqlArgsMerged['limit'] ?? 0,
			'placeholders' => array_values(array_merge($sqlArgsMerged['set'] ?? [], $sqlArgsMerged['where'] ?? []))
		];

		$this->logger
			->add('table_name', $this->tableName)
			->add('table_columns', $this->tableColumns)
			->setSubGroup('sql_args')
			->add('table', $tableSqlArgs)
			->add('method', $methodSqlArgs)
			->add('merged', $sqlArgsMerged)
			->add('prepared', $sqlArgsPrepared)
			->exitSubGroup();

		return $sqlArgsPrepared;
	}

	// Сливает данные из метода с данными конструктора, соблюдая правила замены и дополнения.
	protected function mergeSqlArgs(array $primary, array $secondary): array
	{
		$this->logger
			->add('merge_primary_args', $primary)
			->add('merge_secondary_args', $secondary);
		return [
			// данные из метода заменяют данные из конструктора
			'fields' => $secondary['fields'] ?? $primary['fields'] ?? ['*'],
			'set' => $secondary['set'] ?? $primary['set'] ?? [],
			'limit' => $secondary['limit'] ?? $primary['limit'] ?? 0,
			// данные из метода дополняют данные из конструктора
			'where' => ($primary['where'] ?? []) + ($secondary['where'] ?? []),
			'join' => ($primary['join'] ?? []) + ($secondary['join'] ?? [])
		];
	}

	// Выполняет SQL-запрос.
	public function executeQuery(string $query, array $params = []): mixed
	{
		$query = preg_replace('/\s+/', ' ', trim($query));

		$this->logger
			->setSubGroup('execute_query')
			->add('query', $query)
			->add('params', $params)
			->exitSubGroup();

		$stmt = $this->db->prepare($query);
		$stmt->execute($params);

		$queryType = strtoupper(strtok(trim($query), ' '));

		$response = match ($queryType) {
			'SELECT' => $stmt->fetchAll(PDO::FETCH_ASSOC),
			'INSERT' => $this->db->lastInsertId(),
			'UPDATE', 'DELETE' => $stmt->rowCount(),
			default => throw new \Exception("неподдерживаемый тип запроса: $queryType"),
		};

		$this->logger
			->exitSubGroup()
			->setSubGroup('response')
			->add('response', $response)
			->exitSubGroup()
			->exitSubGroup();

		return $response;
	}

	/**
	 * ============================================
	 * CRUD методы
	 * ============================================
	 */

	// Получает данные из таблицы
	// 
	// Результат запроса:
	// - массив записей, если запрашиваются все поля
	// - массив значений, если запрашивается одно поле
	// - одно значение, если запрашивается одно поле и LIMIT 1
	// - null, если запись не найдена
	public function get(array $args = []): mixed
	{
		$args['method'] = 'get';
		$action = new Get($this, $args);
		return $action->execute();
	}

	// Проверяет существование записи в таблице
	public function exist(array $args = []): bool
	{
		$args['method'] = 'exist';
		$action = new Exist($this, $args);
		return $action->execute();
	}

	// Создает новую запись в таблице
	public function insert(array $args = []): string
	{
		$args['method'] = 'insert';
		$action = new Insert($this, $args);
		return $action->execute();
	}

	// Обновляет существующие записи в таблице
	public function update(array $args = []): int
	{
		$args['method'] = 'update';
		$action = new Update($this, $args);
		return $action->execute();
	}

	// Создает новую запись или обновляет существующую
	public function upsert(array $args = []): string|int
	{
		$args['method'] = 'upsert';
		$action = new Upsert($this, $args);
		return $action->execute();
	}

	// Удаляет записи из таблицы
	public function delete(array $args = []): int
	{
		$args['method'] = 'delete';
		$action = new Delete($this, $args);
		return $action->execute();
	}
}
