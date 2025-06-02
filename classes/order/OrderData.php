<?php

namespace php2steblya\order;

use php2steblya\Script;
use php2steblya\order\utils\OrderData_normalizer;
use php2steblya\order\utils\OrderData_enricher;
use php2steblya\order\utils\OrderData_cleaner;

class OrderData
{
	private array $od;
	private Script $script;

	public function __construct(array $od, Script $script)
	{
		$this->script = $script;
		$this->od = $od;
	}

	public function prepare(): array
	{
		$this->od = OrderData_normalizer::execute($this->od);
		$this->od = OrderData_enricher::execute($this->od, $this->script);
		$this->od = OrderData_cleaner::execute($this->od);
		return $this->od;
	}
}
