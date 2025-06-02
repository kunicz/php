<?php

namespace php2steblya\scripts\db;

use php2steblya\db\DbFactory;
use php2steblya\interfaces\services\ServiceInterface;
use php2steblya\Service;

// скрипт для работы с базой данных через модули.
// использует переданный в scriptData параметр 'request' для вызова модулей и их методов.
class Db extends Service
{
	protected function getService(): ServiceInterface
	{
		return DbFactory::createService();
	}

	protected function checkPermission(array $args): bool
	{
		$conditions = [
			str_starts_with($args['actionName'], 'get') || str_starts_with($args['actionName'], 'exist'),
			!in_array($args['moduleName'], ['telegram_bots', 'telegram_channels'])
		];
		return !in_array(false, $conditions, true);
	}
}
