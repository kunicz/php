<?

namespace php2steblya\api\services;

use php2steblya\db\Db;
use php2steblya\Logger;

class Moysklad_meta
{
	private static $logger;
	private static $db;

	/**
	 * Метод для получения метаданных для заказа в МоемСкладе.
	 * 
	 * @param array $data Массив с аргументами для получения метаданных.
	 * @return array Массив с теми же аргументми, но с метаданными для заказа в МоемСкладе.
	 */
	public static function convert(array $data): array
	{
		if (!self::$logger) self::$logger = Logger::getInstance();
		if (!self::$db) self::$db = Db::createService();

		self::$logger->setSubGroup('формируем мета');

		foreach ($data as $entity => $args) {
			// на всякий случай, всегда интерпретируем аргументы как массив,
			// даже если они переданы в виде одного аргумента,
			// потому что я на данном этапе не могу быть уверен, что в будущем не придется передавать несколько аргументов
			if (!is_array($args)) $args = [$args];

			// сущность может быть стандартной и нестандартной
			// для нестандартных сущностей должна быть создана пара методов (для проверки и для обработки)
			if (self::isAttributeEntity($entity)) {
				$data = self::processAttributeEntity($entity, $data, ...$args);
			} else {
				$data = self::processEntity($entity, $data, $args);
			}
		}

		self::$logger->exitSubGroup();

		return $data;
	}

	// проверяет, является ли аргумент атрибутом (кастомным полем заказа в МоемСкладе)
	private static function isAttributeEntity(string $entity): bool
	{
		$attributesMap = ['orderCrmId'];
		$isAttribute = in_array($entity, $attributesMap);
		return $isAttribute;
	}

	/**
	 * Добавляет атрибут (кастомное поле) в массив attributes заказа МоегоСклада
	 * 
	 * @param string $entity Название сущности, которая будет добавлена в массив attributes
	 * @param array $data Массив данных заказа
	 * @param string $value Значение атрибута
	 * @return array Обновленный массив данных заказа с добавленным атрибутом
	 */
	private static function processAttributeEntity(string $entity, array $data, string $value): array
	{
		self::$logger->setSubGroup($entity);
		if (!isset($data['attributes'])) $data['attributes'] = []; // создаем массив attributes, если он еще не существует
		$attrMap = [
			'orderCrmId' => [
				'entity' => 'customerorder/metadata/attributes',
				'id' => MOYSKLAD_CRM_ORDER_ID
			]
		];
		$attrData = $attrMap[$entity];
		$attr = [
			'value' => (int) $value,
			'meta' => self::build($attrData['entity'], $attrData['id'], false)
		];
		$data['attributes'][] = $attr; // добавляем атрибут в массив attributes
		self::$logger
			->add('attribute', $attr)
			->exitSubGroup();
		return $data;
	}

	/**
	 * Если метод для обработки для сущности существует, подменяет в $data сущность на массив метаданных
	 * 
	 * @param string $entity Название сущности (organization, agent, project, owner)
	 * @param array $data Массив данных заказа
	 * @param array $args Аргументы для получения метаданных сущности
	 * @return array Массив данных заказа
	 */
	private static function processEntity(string $entity, array $data, array $args): array
	{
		$meta = [];
		if (method_exists(self::class, $entity)) {
			self::$logger->setSubGroup($entity);
			$meta = self::$entity(...$args);
			$data[$entity] = ['meta' => $meta];
			self::$logger
				->add('meta', $meta)
				->exitSubGroup();
		}
		return $data;
	}

	/**
	 * Формирует массив meta для запроса к МоемуСкладу
	 * 
	 * @param string $entity Название сущности (organization, agent, project, owner)
	 * @param string $id ID сущности
	 * @param bool $metaHref Флаг для добавления в массив meta ссылки на метаданные сущности
	 * @return array Массив метаданных сущности
	 */
	private static function build(string $entity, $id, $metaHref = true): array
	{
		$enitites = [
			'agent' => 'counterparty',
			'owner' => 'employee',
			'customerorder/metadata/attributes' => 'attributemetadata'
		];
		$meta = [
			'type' => $enitites[$entity] ?? $entity,
			'mediaType' => "application/json",
			'href' => "https://api.moysklad.ru/api/remap/1.2/entity/$entity/$id"
		];
		if ($metaHref) {
			$meta['metadataHref'] = "https://api.moysklad.ru/api/remap/1.2/entity/$entity/metadata";
			$meta['uuidHref'] = "https://online.moysklad.ru/app/#$entity/edit?id=$id";
		}
		return $meta;
	}

	// организация у нас только одна, поэтому даже аргументы можно не передавать.
	private static function organization(): array
	{
		return self::build('organization', MOYSKLAD_ORGANIZATION_ID);
	}

	// принял решение писать все заказы на одного дефолтного контрагента.
	// вероятно решение неоптимальное, но я объективно не вижу, зачем может понадобиться статистика по покупателям в МоемСкладе.
	// вся контактная, финансовая и прочая информация о покупателях хранится в retailcrm.
	private static function agent(): array
	{
		return self::build('counterparty', MOYSKLAD_AGENT_ID);
	}

	// в качестве сущности "проект" в МоемСкладе выступает сайт, на котором был совершен заказ.
	private static function project(string $crmOrder_site): array
	{
		$args = [
			'fields' => ['shop_ms_id'],
			'where' => ['shop_crm_code' => $crmOrder_site],
			'limit' => 1
		];
		$shopMsId = self::$db->shops()->get($args);
		return self::build('project', $shopMsId);
	}

	// в качестве сущности "владелец" в МоемСкладе изначально выступает босс.
	// в срм настроен триггер, который срабатывает при назначении/изменении флориста
	private static function owner(string $crmOrder_florist): array
	{
		$args = [
			'fields' => ['ms_id'],
			'where' => ['slug' => $crmOrder_florist],
			'limit' => 1
		];
		$ownerId = self::$db->employees()->get($args);
		return self::build('employee', $ownerId);
	}
}
