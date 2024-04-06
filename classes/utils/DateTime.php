<?

namespace php2steblya\utils;

class DateTime
{
	public static function calculateMinutesFromNowTo($date)
	{
		$date = new \DateTime($date);
		$now = new \DateTime();
		$interval = $date->diff($now);
		// Convert the difference to minutes
		$minutesDifference = $interval->days * 24 * 60;
		$minutesDifference += $interval->h * 60;
		$minutesDifference += $interval->i;
		return $minutesDifference;
	}
}
