<?php

namespace php2steblya\db\tables;

use php2steblya\db\DbTable;

class Any extends DbTable
{
	protected function tableSqlArgs(array $data = []): array
	{
		return $data;
	}

	protected function methodSqlArgs(array $data = []): array
	{
		return $data;
	}
}
