<?php

// заглушка чтоб не ругалась ide! деплоить нельзя!

namespace Dotenv;

class Dotenv
{
	public static function createImmutable(string $path): Dotenv
	{
		return new Dotenv();
	}

	public function load(): void {}
}
