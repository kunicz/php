<?

namespace php2steblya\utils;

use php2steblya\DB;

class Input
{
	public static function sanitize($input)
	{
		return htmlspecialchars(trim($input));
	}
}
