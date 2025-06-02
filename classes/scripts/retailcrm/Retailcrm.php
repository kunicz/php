<?php

namespace php2steblya\scripts\retailcrm;

use php2steblya\api\ApiFactory;
use php2steblya\interfaces\services\ServiceInterface;
use php2steblya\Service;

// скрипт для работы с api retailCrm через модули.
// использует переданный в scriptData параметр 'request' для вызова модулей и их методов.
class Retailcrm extends Service
{
	protected function getService(): ServiceInterface
	{
		return ApiFactory::createService('retailcrm');
	}

	protected function checkPermission(array $args): bool
	{
		return str_starts_with($args['actionName'], 'get');
	}
}
