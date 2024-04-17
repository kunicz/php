<?

namespace php2steblya;

use php2steblya\Finish;
use php2steblya\Logger;

class Script
{
	/**
	 * инициализируем класс скрипта
	 */
	public static function initClass($scriptData = [])
	{
		try {
			$className = $scriptData['script'];
			$class = 'php2steblya\\scripts\\' . $className;
			if (!class_exists($class)) throw new \Exception("script ($className) not found");
			$scriptInstance = new $class($scriptData);
			$scriptInstance->init();
		} catch (\Exception $e) {
			$logger = Logger::getInstance();
			$logger->addToLog('scriptData', $scriptData);
			Finish::fail($e);
		}
	}
}
