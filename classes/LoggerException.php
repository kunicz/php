<?

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\ApiTelegramBot as Api;

class LoggerException extends \Exception
{
	private $error;

	public function __construct($error)
	{
		$this->error = $error;
	}
	public function abort(Logger $log)
	{
		$log->pushError($this->error);
		$log->writeSummary();
		$args = [
			'chat_id' => 165817187,
			'text' => $log->print()
		];
		$api = new Api('employee');
		$api->post('sendMessage', $args);
		die($log->getJson());
	}
}
