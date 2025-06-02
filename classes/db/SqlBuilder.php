<?php

namespace php2steblya\db;

class SqlBuilder
{

	/**
	 * Формирует строку условий для WHERE или SET
	 *
	 * @param array $data Массив с условиями
	 * @param string $type Тип условий ('where' или 'set')
	 * @return string Строка условий
	 * @throws \Exception если передан неподдерживаемый тип условий
	 */
	public static function buildConditionsString(array $data, string $type): string
	{
		if (empty($data)) return '';

		$parts = [];
		foreach ($data as $key => $value) {
			switch ($type) {
				case 'where':
					$connector = ' AND ';
					$operator = (is_array($value) && isset($value['operator'])) ? strtoupper($value['operator']) : '=';
					break;
				case 'set':
					$connector = ', ';
					$operator = '=';
					break;
				default:
					throw new \Exception("неподдерживаемый тип условий: $type");
			}
			$parts[] = "$key $operator ?";
		}

		return implode($connector, $parts);
	}

	/**
	 * Формирует строку JOIN для SQL-запроса
	 *
	 * @param array $joinData Массив с данными о соединениях
	 * @return string Строка JOIN
	 * @throws \Exception если не передан 'on' для join
	 */
	public static function buildJoin(array $joinData): string
	{
		if (empty($joinData)) return '';

		$parts = [];
		foreach ($joinData as $table => $condition) {
			if (empty($condition['on'])) {
				throw new \Exception("не передан 'on' для join для таблицы '$table'");
			}
			$type = strtoupper($condition['type'] ?? 'INNER');
			$on = $condition['on'] ?? '';
			$parts[] = "{$type} JOIN {$table} ON {$on}";
		}

		return ' ' . implode(' ', $parts);
	}

	/**
	 * Формирует строку WHERE для SQL-запроса
	 *
	 * @param array $whereData Массив с условиями WHERE
	 * @return string Строка WHERE
	 */
	public static function buildWhere(array $whereData): string
	{
		$w = self::buildConditionsString($whereData, 'where');
		return $w ? " WHERE {$w}" : '';
	}

	/**
	 * Формирует строку LIMIT для SQL-запроса
	 *
	 * @param int $limit Ограничение количества результатов
	 * @return string Строка LIMIT
	 */
	public static function buildLimit(int $limit): string
	{
		return $limit ? " LIMIT {$limit}" : '';
	}

	/**
	 * Формирует строку SET для SQL-запроса
	 *
	 * @param array $setData Массив с данными для установки
	 * @return string Строка SET
	 */
	public static function buildSet(array $setData): string
	{
		$s = self::buildConditionsString($setData, 'set');
		return $s ? " SET {$s}" : '';
	}

	/**
	 * Формирует строку VALUES для SQL-запроса INSERT
	 *
	 * @param array $setData Массив с данными для вставки
	 * @return string Строка VALUES
	 */
	public static function buildValues(array $setData): string
	{
		if (empty($setData)) return '';

		$columns = array_keys($setData);
		$placeholders = array_fill(0, count($columns), '?');

		return " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
	}
}
