<?php

namespace php2steblya\db\tables;

use php2steblya\db\Db;
use php2steblya\db\DbTable;

class Products extends DbTable
{
	const SHOP_FILTER_FIELDS = ['shop_tilda_id', 'shop_crm_code'];

	/**
	 * Строит условия для SQL-запросов в зависимости от переданных данных.
	 * В этом методе на основе переданных фильтров строятся условия 'where' для SQL-запроса.
	 * Если фильтры по магазину передаются через 'shop_crm_id', 'shop_tilda_id' или 'shop_crm_code',
	 * они проверяются и конвертируются в идентификатор магазина.
	 *
	 * @param array $args Массив данных для фильтрации.
	 * @return array Массив с условиями для запроса (например, 'where').
	 * @throws \Exception Если не удалось определить shop_crm_id`.
	 */
	protected function tableSqlArgs(array $args = []): array
	{
		$this->logger->add('table_where_initial', $args['where'] ?? []);

		// Если фильтр по 'where' задан, shop_crm_id не задан
		if (!empty($args['where']) && empty($args['shop_crm_id'])) {

			// Проходим по фильтрам, проверяем на наличие ключей из SHOP_FILTER_FIELDS
			foreach (self::SHOP_FILTER_FIELDS as $field) {
				if (!isset($args['where'][$field])) continue;
				$shop = Db::createService()->shops()->get(['where' => [$field => $args['where'][$field]], 'limit' => 1]);
				$this->logger->add('shop', $shop);
				if (empty($shop)) continue;
				$args['where'] = ['shop_crm_id' => $shop['shop_crm_id']];
				break;
			}

			if (empty($args['where']['shop_crm_id'])) {
				throw new \Exception("не передан shop_crm_id для продукта");
			}
		}

		$this->logger->add('table_where_final', $args['where'] ?? []);

		return $args;
	}

	protected function methodSqlArgs(array $args = []): array
	{
		$this->logger->add('method_where_initial', $args['where'] ?? []);

		if (!empty($args['where'])) {
			$args['where'] = array_filter($args['where'], fn($v, $k) => !in_array($k, self::SHOP_FILTER_FIELDS), ARRAY_FILTER_USE_BOTH);
		}

		$this->logger->add('method_where_final', $args['where'] ?? []);

		return $args;
	}

	/**
	 * Получает список товаров-новинок для указанного магазина.
	 *
	 * Новинки — это товары, созданные за последние 2 месяца,
	 * которые не находятся на витрине (определяется по условию `id != vitrina_id OR vitrina_id IS NULL`)
	 * и не имеют установленного типа (`type IS NULL`).
	 *
	 * Магазин определяется на основе переданных фильтров (например, `shop_crm_code`, `shop_tilda_id`),
	 * которые конвертируются в `shop_crm_id` методом {@see tableSqlArgs()}.
	 *
	 * @param array $args Ассоциативный массив с аргументами фильтрации.
	 * @return array Список товаров-новинок в виде массива ассоциативных массивов.
	 * @throws \Exception Если не удалось определить shop_crm_id`.
	 */
	public function getNovinki(array $args = []): mixed
	{
		$args['method'] = 'getNovinki'; // для логгирования
		$tableSqlArgs = $this->tableSqlArgs($args);
		$shop_crm_id = $tableSqlArgs['where']['shop_crm_id'];

		$sql = "
			SELECT id, createdOn, title
			FROM products
			WHERE shop_crm_id = ?
				AND type IS NULL
				AND (id != vitrina_id OR vitrina_id IS NULL)
				AND createdOn >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 MONTH)
			ORDER BY createdOn DESC
		";

		return $this->executeQuery($sql, [$shop_crm_id]);
	}

	/**
	 * Получает доступность товаров по их ID и названиям магазинов.
	 *
	 * Используется для проверки поля `allowed_today` у товаров, соответствующих указанным product ID и `shop_title`.
	 *
	 * @param array $args Массив с параметрами: ids: массива ID товаров и shops: массив названий магазинов
	 * @return array Массив записей с полями `id` и `allowed_today`.
	 * @throws \Exception Если отсутствуют входные данные.
	 */
	public function getAllownessByIds(array $args = []): array
	{
		if (empty($args['ids']) || empty($args['shops'])) throw new \Exception('Не переданы необходимые аргументы: ids и shops');

		$args['method'] = 'getAllownessByIds';
		$idPlaceholders = implode(',', array_fill(0, count($args['ids']), '?'));
		$shopPlaceholders = implode(',', array_fill(0, count($args['shops']), '?'));
		$sql = "
			SELECT p.id, p.allowed_today
			FROM products p
			JOIN shops s ON p.shop_crm_id = s.shop_crm_id
			WHERE p.id IN ($idPlaceholders)
			AND s.shop_title IN ($shopPlaceholders)
		";
		return $this->executeQuery($sql, array_merge($args['ids'], $args['shops']));
	}

	/**
	 * Устанавливает значение поля `allowed_today` у конкретного товара, принадлежащего магазину с заданным `shop_title`.
	 *
	 * @param array $args Массив с параметрами: id товара, название магазина, allowed_today (1,0,-1)
	 * @return int Кол-во затронутых строк
	 * @throws \Exception Если не заданы обязательные параметры
	 */
	public function setAllownessById(array $args = []): int
	{
		if (!isset($args['id'])) throw new \Exception('id not set');
		if (!isset($args['shop'])) throw new \Exception('shop not set');
		if (!isset($args['allowed_today'])) throw new \Exception('allowed_today not set');

		$args['method'] = 'setAllownessByProductId';
		$sql = "
			UPDATE products p
			JOIN shops s ON p.shop_crm_id = s.shop_crm_id
			SET p.allowed_today = :allowed_today
			WHERE p.id = :id
			AND s.shop_title = :shop_title
		";
		return $this->executeQuery($sql, ['id' => $args['id'], 'shop_title' => $args['shop'], 'allowed_today' => $args['allowed_today']]);
	}
}
