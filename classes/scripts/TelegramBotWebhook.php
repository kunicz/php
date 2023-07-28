<?

namespace php2steblya\scripts;

use php2steblya\File;
use php2steblya\Logger;
use php2steblya\Script;
use php2steblya\DB_stf as DB;
use php2steblya\ApiTelegramBot as Api;
use php2steblya\TelegramBot_chat as Chat;
use php2steblya\LoggerException as Exception;

class TelegramBotWebhook extends Script
{
	public $log;
	private $api;
	private $update;

	public function init()
	{
		$this->log = new Logger('telegram bot webhook');
		$this->api = new Api($this->scriptData['bot']);
		try {
			if (!isset($this->scriptData['bot'])) throw new Exception('bot title not set');
			$this->log->push('bot title', $this->scriptData['bot']);
			$this->setWebhook();
			$this->processWebhook();
		} catch (Exception $e) {
			$this->log->pushError($e->getMessage());
			$e->abort($this->log);
		}
	}

	/**
	 * работа вебхука
	 */
	private function processWebhook()
	{
		http_response_code(200);
		$this->update = json_decode(file_get_contents('php://input'), true);
		if (isset($this->update['message']) || isset($this->update['callback_query'])) {
			$db = new DB();
			$chat = new Chat($db, $this->api, $this->scriptData['bot'], $this->update);
			$this->log->push('chat', $chat->getLog());
		}
		$file = new File(dirname(dirname(dirname(__FILE__))) . '/TelegramBotLastUpdate.txt');
		$file->write(print_r($this->update, true));
	}

	/**
	 * связываем бот и вебхук
	 */
	private function setWebhook()
	{
		if (!isset($this->scriptData['set'])) return;
		$args = [
			'url' => 'https://php.2steblya.ru/?script=TelegramBotWebhook&bot=' . $this->scriptData['bot']
		];
		$this->api->post('setWebhook', $args);
		if ($this->api->hasErrors()) {
			$this->log->pushError($this->api->getError());
		}
		$this->log->push('response', $this->api->response);
	}
}
