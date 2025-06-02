<?php

namespace php2steblya\order\utils;

class OrderData_cleaner
{
	public static function execute(array $od): array
	{
		// фейковый флаг, не участвующий в логике
		// передается через вебхук для различия эндпоинтов при совершенном и оплаченном заказах
		unset($od['paid']);

		// неужные поля из тильды
		// полностью заменены на мои собственные в самой od
		unset($od['payment']['delivery_price']);
		unset($od['payment']['delivery_fio']);
		unset($od['payment']['delivery_address']);
		unset($od['payment']['delivery_comment']);
		unset($od['payment']['delivery']);

		// если будут чесаться руки удалить formid, то не стоит
		// на нем завязана проверка isTildaTest

		return $od;
	}
}
