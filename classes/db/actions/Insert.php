<?php

namespace php2steblya\db\actions;

use php2steblya\db\DbAction;
use php2steblya\db\SqlBuilder;

class Insert extends DbAction
{
	/**
	 * Валидация аргументов перед выполнением запроса
	 * @throws \Exception если не переданы данные для вставки
	 */
	protected function validate(): void
	{
		if (empty($this->args['set'])) {
			throw new \Exception("не переданы данные для вставки");
		}
	}

	/**
	 * Построение SQL-запроса для вставки данных
	 * @return string SQL-запрос
	 */
	protected function buildQuery(): string
	{
		return "INSERT INTO {$this->table->getTableName()}"
			. SqlBuilder::buildValues($this->args['set']);
	}
}
