<?

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\LoggerException as Exception;

class TelegramBot_state_factory
{
	public function createState($bot, $chat)
	{
		try {
			$className = 'php2steblya\TelegramBot_state_' . $bot . '_' . $chat->state;
			if (!class_exists($className)) throw new Exception('class ' . $className . ' does not exist');
			return new $className($bot, $chat);
		} catch (Exception $e) {
			$log = new Logger('TelegramBot_state_factory');
			$e->abort($log);
		}
	}
}
