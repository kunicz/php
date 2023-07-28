<?

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\LoggerException as Exception;

class ApiTelegramBot extends Api
{
	private $botId;
	private $botName;
	private $botTitle;

	public function __construct($bot)
	{
		try {
			$this->botTitle = strtoupper($bot);
			if (!isset($_ENV['TELEGRAM_BOT_' . $this->botTitle . '_ID'])) throw new Exception("bot $this->botTitle is not registered");
			$this->botId = $_ENV['TELEGRAM_BOT_' . $this->botTitle . '_ID'];
			$this->token = $_ENV['TELEGRAM_BOT_' . $this->botTitle . '_TOKEN'];
			$this->botName = $_ENV['TELEGRAM_BOT_' . $this->botTitle . '_NAME'];
			$this->adres = 'https://api.telegram.org/bot' . $this->botId . ':' . $this->token;
		} catch (Exception $e) {
			$log = new Logger('Telegram Bot Api');
			$log->push('botTitle', $this->botTitle);
			$e->abort($log);
		}
	}

	public function getError()
	{
		$error = $this->response->description;
		return $error;
	}

	public function hasErrors()
	{
		return !$this->response->ok;
	}

	public function getBotId()
	{
		return $this->botId;
	}

	public function getBotName()
	{
		return $this->botName;
	}

	public function getBotTitle()
	{
		return $this->botTitle;
	}
}
