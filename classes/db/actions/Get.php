<?php

namespace php2steblya\db\actions;

use php2steblya\db\DbAction;
use php2steblya\db\SqlBuilder;

class Get extends DbAction
{
	/**
	 * Построение SQL-запроса для получения данных
	 * @return string SQL-запрос
	 */
	protected function buildQuery(): string
	{
		return "SELECT {$this->args['fields']} FROM {$this->table->getTableName()}"
			. SqlBuilder::buildJoin($this->args['join'] ?? [])
			. SqlBuilder::buildWhere($this->args['where'] ?? [])
			. SqlBuilder::buildLimit($this->args['limit'] ?? 0);
	}

	/**
	 * Обработка результата запроса
	 * @param mixed $response Результат выполнения запроса
	 * @return mixed Обработанный результат
	 */
	protected function processResponse(mixed $response): mixed
	{
		// Проверка наличия полей для выборки
		$fieldsStr = $this->args['fields'] ?? '*';
		$isSpecificFields = $fieldsStr !== '*';

		// Если запрос не вернул данные, возвращаем пустой массив
		if (empty($response)) return [];

		// Если запрошен один результат (limit = 1), возвращаем его напрямую
		if (($this->args['limit'] ?? 0) == 1) {
			// Если был запрос на конкретные поля, обрабатываем результат
			if ($isSpecificFields) {
				$fields = explode(',', str_replace(' ', '', $fieldsStr));

				// Если запрошено только одно поле, возвращаем его значение
				if (count($fields) === 1) {
					$field = trim($fields[0]);
					return $response[0][$field] ?? null;
				}
			}
			return $response[0] ?? null;
		}

		return $response;
	}
}
