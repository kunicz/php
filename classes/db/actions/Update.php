<?php

namespace php2steblya\db\actions;

use php2steblya\db\DbAction;
use php2steblya\db\SqlBuilder;

class Update extends DbAction
{
	/**
	 * Валидация аргументов перед выполнением запроса
	 * @throws \Exception если не переданы данные для обновления
	 */
	protected function validate(): void
	{
		if (empty($this->args['set'])) {
			throw new \Exception("не переданы данные для обновления");
		}
	}

	/**
	 * Построение SQL-запроса для обновления данных
	 * @return string SQL-запрос
	 */
	protected function buildQuery(): string
	{
		return "UPDATE {$this->table->getTableName()}"
			. SqlBuilder::buildSet($this->args['set'])
			. SqlBuilder::buildWhere($this->args['where'] ?? []);
	}
}
