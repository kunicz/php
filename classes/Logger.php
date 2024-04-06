<?

namespace php2steblya;

use php2steblya\File;
use php2steblya\telegram\Response_sendMessage_post;

class Logger
{
	private static $instance;
	private $logData = [];

	private function __construct()
	{
		// Private constructor to prevent instantiation
	}

	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function addToLog($key, $value = null)
	{
		$this->logData[$key] = $value ?: 'none';
	}

	public function sendToAdmin()
	{
		$time = date('Y-m-d-H-i-s');

		//отправляем сообщение
		$message = [
			date('d.m.Y H:i:s'),
			'<b>script</b>: ' . $this->logData['script'],
			'<b>error_file</b>: ' . $this->logData['error_file'],
			'<b>error_message</b>: ' . $this->logData['error_message'],
			'<b>logger_data</b> : <a href="https://php.2steblya.ru/error_logs/' . $time . '.json">' . $time . '.json</a>'
		];
		$args = [
			'chat_id' => $_ENV['telegram_admin_chat_id'],
			'parse_mode' => 'HTML',
			'text' => implode("\r\n", $message)
		];
		$telegram = new Response_sendMessage_post('admin');
		$telegram->sendMessage($args);

		//записываем лог в файл
		$file = new File('/home/k/kuniczw4/php.2steblya.ru/public_html/error_logs/' . $time . '.json');
		$file->write(json_encode($this->logData, JSON_PRETTY_PRINT));
	}

	public static function shortenPath($string)
	{
		return str_replace('/home/k/kuniczw4/php.2steblya.ru/public_html/classes/', '', $string);
	}

	public function getLogData()
	{
		return $this->logData;
	}
}
