<?php

namespace php2steblya\order\handlers;

use php2steblya\db\DbService;
use php2steblya\Logger;
use php2steblya\api\ApiService;
use php2steblya\Script;

abstract class Abstract_handler
{
	protected array $od;
	protected Script $script;
	protected DbService $db;
	protected Logger $logger;
	protected ApiService $retailcrm;
	protected ApiService $telegram;
	protected ApiService $moysklad;

	public function __construct(array $od, Script $script)
	{
		$this->od = $od;
		$this->script = $script;
		$this->logger = $script->logger;
		$this->logger->setGroup('handler_' . $this->getClassName());
	}

	// имя наследника класса (например, 'db', 'telegram' и т.д.)
	protected function getClassName()
	{
		$classParts = explode('\\', get_class($this));
		return strtolower(end($classParts));
	}
}
