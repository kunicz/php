<?php

namespace php2steblya\db\actions;

use php2steblya\db\DbAction;
use php2steblya\db\SqlBuilder;

class Exist extends DbAction
{
	/**
	 * Построение SQL-запроса для проверки существования записи
	 * @return string SQL-запрос
	 */
	protected function buildQuery(): string
	{
		return "SELECT 1 FROM {$this->table->getTableName()}"
			. SqlBuilder::buildJoin($this->args['join'] ?? [])
			. SqlBuilder::buildWhere($this->args['where'] ?? [])
			. " LIMIT 1";
	}

	/**
	 * Обработка результата запроса
	 * @param mixed $response Результат выполнения запроса
	 * @return bool Существует ли запись
	 */
	protected function processResponse(mixed $response): bool
	{
		return !empty($response);
	}
}
