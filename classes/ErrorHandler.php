<?php

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\Script;

class ErrorHandler
{
	public static function init()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline) {
			Logger::getInstance()->addErrorHandler('set_error_handler');
			self::handle([
				'message' => $errstr,
				'code' => $errno,
				'file' => $errfile,
				'line' => $errline
			]);
			return false;
		});

		set_exception_handler(function (\Throwable $e) {
			Logger::getInstance()->addErrorHandler('set_exception_handler');
			Script::fail($e);
		});

		register_shutdown_function(function () {
			$error = error_get_last();
			if (!$error) return;
			Logger::getInstance()->addErrorHandler('register_shutdown_function');
			self::handle($error);
		});
	}

	private static function handle(array $error): void
	{
		// Если сообщение об ошибке пустое, задаем значение по умолчанию
		$message = $error['message'] ?? 'Undefined error';
		$code = $error['code'] ?? 0;
		$file = $error['file'] ?? '';
		$line = $error['line'] ?? 0;

		// Создаем объект исключения
		$e = new \ErrorException(
			$message,
			$code,
			0, // Severity
			$file,
			$line
		);

		// Передаем исключение в Script::fail для обработки
		Script::fail($e);
	}
}
