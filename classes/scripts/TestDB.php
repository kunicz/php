<?

namespace php2steblya\scripts;

use php2steblya\DB_stf as DB;
use php2steblya\Logger;

class TestDB
{
	public $log;

	public function init()
	{
		$db = new DB;
		$this->log = new Logger('test db');
		$data = [
			'chat_id' => 165817187,
			'state' => uniqid()
		];
		//$e = $db->getTelegramChat($_ENV['TELEGRAM_BOT_ADMIN_ID'], 165817187);
		$e = $db->getTelegramChat('admin', 165817187);
		$this->log->push('db', $db->getLog());
	}
}
