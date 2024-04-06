<?

namespace php2steblya\telegram;

use php2steblya\Logger;
use php2steblya\telegram\Api;

class Response
{
	protected array $botArgs;
	protected string $botName;
	protected string $botMethod;
	protected ?object $response;

	public function request($botName, $httpMethod)
	{
		try {
			$api = new Api($botName);
			$api->curl($httpMethod, $this->botMethod, $this->botArgs);
			$this->response = $api->getResponse();
			if ($api->hasErrors()) throw new \Exception($api->getError());
		} catch (\Exception $e) {
			$logger = Logger::getInstance();
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			$logger->addToLog('telegram_response_bot', $this->botName);
			$logger->addToLog('telegram_response_args', $this->botArgs);
			$logger->addToLog('telegram_response_method', $this->botMethod . ' (' . $httpMethod . ')');
			$logger->addToLog('telegram_response_response', $this->response);
			$logger->sendToAdmin();
		}
	}
}
