<?

namespace php2steblya;

use php2steblya\Logger;

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
		die($log->getJson());
	}
}
