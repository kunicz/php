<?

namespace php2steblya\telegram;

use php2steblya\DB;
use php2steblya\Logger;

class Api extends \php2steblya\Api
{
	private $id;

	public function __construct($botName)
	{
		try {
			$db = DB::getInstance();
			$response = $db->sql("SELECT telegram_id,token FROM telegram_bots WHERE name = '$botName'");
			if (empty($response)) {
				throw new \Exception("bot \"$botName\" not found in db");
			} else {
				$this->id 		= $response[0]->telegram_id;
				$this->token 	= $response[0]->token;
				$this->adres 	= 'https://api.telegram.org/bot' . $this->id . ':' . $this->token;
				$this->args		= [];
			}
		} catch (\Exception $e) {
			$logger = Logger::getInstance();
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			$logger->sendToAdmin();
		}
	}

	public function getError()
	{
		return $this->response->description;
	}

	public function hasErrors()
	{
		return !$this->response->ok;
	}
}
