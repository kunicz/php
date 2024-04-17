<?

namespace php2steblya;

use php2steblya\Logger;

class Finish
{
	static function success($key = null, $value = null)
	{
		$logger = Logger::getInstance();
		$return = [
			'success' => 1,
			'logger' => $logger->getLogData()
		];
		if ($key) $return[$key] = $value;
		die(json_encode($return));
	}

	static function fail($e)
	{
		$logger = Logger::getInstance();
		$logger->addToLog('error_message', $e->getMessage());
		$logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
		$logger->sendToAdmin();
		die(json_encode([
			'success' => 0,
			'logger' => $logger->getLogData()
		]));
	}
}
