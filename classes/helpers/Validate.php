<?php

namespace php2steblya\helpers;

class Validate
{
	public static function notEmpty(mixed $value, string $message)
	{
		if (empty($value)) throw new \Exception($message);
	}
}
