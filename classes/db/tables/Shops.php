<?php

namespace php2steblya\db\tables;

class Shops extends Any
{
	protected function tableSqlArgs(array $data = []): array
	{
		$s = 'shops';
		$c = 'cities';
		$ctr = 'countries';
		return [
			'fields' => ["$s.*", "$c.*", "$ctr.*"],
			'join' => [
				$c => ['on' => "$s.city_id = $c.city_id"],
				$ctr => ['on' => "$ctr.country_id = $c.country_id"]
			]
		];
	}

	protected function methodSqlArgs(array $data = []): array
	{
		return $data;
	}
}
