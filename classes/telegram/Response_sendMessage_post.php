<?

namespace php2steblya\telegram;

use php2steblya\Logger;

class Response_sendMessage_post extends Response
{
	public function __construct($botName)
	{
		$this->botName = $botName;
		$this->botMethod = 'sendMessage';
	}

	public function sendMessage(array $args)
	{
		$this->botArgs = $args;
		$this->request($this->botName, 'post');
		$logger = Logger::getInstance();
		$logger->addToLog('telegram_sendMessage_file', Logger::shortenPath(__FILE__));
		$logger->addToLog('telegram_sendMessage_method', $this->botMethod);
		$logger->addToLog('telegram_sendMessage_args', $this->botArgs);
		$logger->addToLog('telegram_sendMessage_bot', $this->botName);
		$logger->addToLog('telegram_sendMessage_response', $this->response);

		return $this->response;
	}
}
