<?php

namespace php2steblya\helpers;

class StringCase
{
	public static function snake(string $str)
	{
		$str = preg_replace('/([a-z])([A-Z])/u', '$1_$2', $str);
		$str = preg_replace('/[\s\-]+/u', '_', $str);
		return mb_strtolower($str);
	}

	public static function pascal(string $str)
	{
		$str = str_replace(['-', '_'], ' ', $str);
		$str = mb_convert_case($str, MB_CASE_TITLE);
		return str_replace(' ', '', $str);
	}

	public static function kebab(string $str)
	{
		$str = preg_replace('/([a-z])([A-Z])/u', '$1-$2', $str);
		$str = preg_replace('/[\s_]+/u', '-', $str);
		return mb_strtolower($str);
	}

	public static function camel(string $str)
	{
		$str = self::pascal($str);
		return lcfirst($str);
	}
}
