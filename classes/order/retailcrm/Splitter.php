<?php

namespace php2steblya\order\retailcrm;

use php2steblya\order\retailcrm\splitter_strategies\Donat;
use php2steblya\order\retailcrm\splitter_strategies\Podpiska;
use php2steblya\order\retailcrm\splitter_strategies\Normal;
use php2steblya\order\retailcrm\Calculator;
use php2steblya\Script;

class Splitter
{
	public static function execute(array $od, Script $script): array
	{
		$orders = [];
		$products = $od['payment']['products'];
		$income = $od['payment']['income'];
		$strategies = [
			new Donat($od, $products, $script), // донаты
			new Normal($od, $products, $script), // нормальные + допники или только допники
			new Podpiska($od, $products, $script) // подписки + допники
		];
		$logger = $script->logger;

		foreach ($strategies as $strategy) {
			if (empty($products)) break;

			$strategyName = $strategy->strategyName();
			$logger->setSubGroup('strategy_' . $strategyName);
			$orders = array_merge($orders, $strategy->execute());
			$orders = Calculator::execute($orders, $strategyName, $income, $script);
			$logger->exitSubGroup();
		}

		return $orders;
	}
}
