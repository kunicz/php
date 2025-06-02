<?php

namespace php2steblya\db;

use php2steblya\db\DbTable;

abstract class DbAction
{
	protected DbTable $table;
	protected array $args;

	public function __construct(DbTable $table, array $args)
	{
		$this->table = $table;
		$this->args = $this->processArgs($args);
	}

	// Главный метод выполнения операции
	public function execute(): mixed
	{
		$this->validate();
		$query = $this->buildQuery();
		$params = $this->getParams();
		$response = $this->table->executeQuery($query, $params);
		return $this->processResponse($response);
	}

	// Валидация аргументов (переопределяется в потомках)
	protected function validate(): void {}

	// Построение SQL-запроса (переопределяется в потомках)
	abstract protected function buildQuery(): string;

	// Получение параметров для запроса (переопределяется в потомках при необходимости)
	protected function getParams(): array
	{
		return $this->args['placeholders'];
	}

	// Обработка аргументов
	protected function processArgs($args): array
	{
		return $this->table->getSqlArgs($args);
	}

	// Обработка результата (переопределяется в потомках при необходимости)
	protected function processResponse(mixed $response): mixed
	{
		return $response;
	}
}
