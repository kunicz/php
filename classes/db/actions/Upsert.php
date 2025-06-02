<?php

namespace php2steblya\db\actions;

use php2steblya\db\DbAction;
use php2steblya\db\SqlBuilder;

class Upsert extends DbAction
{
	/**
	 * Валидация аргументов перед выполнением запроса
	 * @throws \Exception если не переданы данные для вставки/обновления
	 */
	protected function validate(): void
	{
		if (empty($this->args['set'])) {
			throw new \Exception("не переданы данные для вставки/обновления");
		}
	}

	/**
	 * Построение SQL-запроса для вставки/обновления данных
	 * @return string SQL-запрос
	 */
	protected function buildQuery(): string
	{
		$setStr = SqlBuilder::buildConditionsString($this->args['set'], 'set');

		return "INSERT INTO {$this->table->getTableName()}"
			. " SET {$setStr}"
			. " ON DUPLICATE KEY UPDATE {$setStr}";
	}

	/**
	 * Получение параметров для запроса
	 * @return array Массив параметров
	 */
	protected function getParams(): array
	{
		// Дублируем параметры, так как они используются и в INSERT, и в UPDATE
		return array_merge($this->args['placeholders'], $this->args['placeholders']);
	}
}
