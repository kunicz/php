<?php

namespace php2steblya\scripts\moysklad;

use php2steblya\api\ApiFactory;
use php2steblya\interfaces\services\ServiceInterface;
use php2steblya\Service;

// скрипт для работы с api retailCrm через модули.
// использует переданный в scriptData параметр 'request' для вызова модулей и их методов.
class MoySklad extends Service
{
	protected function getService(): ServiceInterface
	{
		return ApiFactory::createService('moysklad');
	}

	protected function checkPermission(array $args): bool
	{
		return str_starts_with($args['actionName'], 'get');
	}
}
