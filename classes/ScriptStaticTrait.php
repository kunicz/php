<?php

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\api\Api;

trait ScriptStaticTrait
{
	private static string $time;

	// завершает выполнение скрипта с успешным результатом.
	public static function success(mixed $response, bool $log = false): void
	{
		self::finishedAt();
		$return = [
			'success' => true,
			'response' => $response
		];
		if ($log) {
			$return['logger'] = Logger::getInstance()->getLogData();
		}
		self::die($return, 200);
	}

	// завершает выполнение скрипта с ошибкой.
	public static function fail(\Throwable $e, int $status = 400): void
	{
		Logger::getInstance()->addError($e);
		self::finishedAt();
		self::processException($e);
		$return = [
			'success' => false,
			'response' => $e->getMessage(),
			'logger' => Logger::getInstance()->getLogData()
		];
		self::die($return, $status);
	}

	// завершает выполнение скрипта и отправляет JSON-ответ.
	private static function die(array $return, int $status): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=UTF-8');
		die(json_encode($return, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}

	// уведомляет администратора о некритической ошибке.
	public static function notifyAdmin(\Throwable $e): void
	{
		self::processException($e);
	}

	// обрабатывает исключение, добавляет его в лог, уведомляет администратора и сохраняет лог в файл.
	private static function processException(\Throwable $e): void
	{
		self::sendTelegramMessage(self::buildTelegramMessage($e));
		self::saveLogToFile();
	}

	// формирует сообщение для отправки в Telegram.
	private static function buildTelegramMessage(\Throwable $e): string
	{
		$logData = Logger::getInstance()->getLogData();
		$message = [];
		$message[] = date('d.m.Y H:i:s');
		$message[] = '<b>source</b>: ' . $logData['source'];
		$message[] = '<b>script</b>: ' . $logData['script_data']['$_GET']['script'] ?? 'undefined';
		$message[] = '<b>msg</b>: ' . $e->getMessage();
		$message[] = '<b>file</b>: ' . $e->getFile();
		$message[] = '<b>line</b>: ' .	$e->getLine();
		$message[] = '<b>log</b>: <a href="https://php.2steblya.ru/logs/errors/' . self::getTime() . '.json">' . self::getTime() . '.json</a>';
		return implode(PHP_EOL, $message);
	}

	// отправляет сообщение в Telegram.
	private static function sendTelegramMessage(string $message): void
	{
		try {
			Logger::getInstance()->setGroup('отправляем отчет об ошибке');
			$args = [
				'chat_id' => $_ENV['TELEGRAM_ADMIN_CHAT_ID'],
				'parse_mode' => 'HTML',
				'text' => $message
			];
			Api::createService('telegram')->setBotName('admin')->messages()->send($args);
		} catch (\Throwable $e) {
			Logger::getInstance()->addError($e);
		}
	}

	// сохраняет лог в файл.
	private static function saveLogToFile(): void
	{
		$file = new File($_ENV['PROJECT_PATH'] . '/logs/errors/' . self::getTime() . '.json');
		$file->write(json_encode(Logger::getInstance()->getLogData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}

	// устанавливает один раз временную метку для текущего процесса и возвращает ее.
	private static function getTime(): string
	{
		if (!isset(self::$time)) self::$time = date('Y-m-d H-i-s');
		return self::$time;
	}

	// записывает время завершения скрипта в лог.
	private static function finishedAt(): void
	{
		Logger::getInstance()->addRoot('time_end', self::getTime());
	}
}
