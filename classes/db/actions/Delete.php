<?php

namespace php2steblya\db\actions;

use php2steblya\db\DbAction;
use php2steblya\db\SqlBuilder;

class Delete extends DbAction
{
	/**
	 * Построение SQL-запроса для удаления данных
	 * @return string SQL-запрос
	 */
	protected function buildQuery(): string
	{
		return "DELETE FROM {$this->table->getTableName()}"
			. SqlBuilder::buildJoin($this->args['join'] ?? [])
			. SqlBuilder::buildWhere($this->args['where'] ?? []);
	}
}
