<?php

namespace php2steblya\helpers;

class DateTime
{
	// вычисляет количество минут от текущего времени до указанной даты.
	public static function minutesFromNowTo(string $date): int
	{
		$now = new \DateTime();
		$targetDate = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
		if (!$targetDate) {
			throw new \Exception("некорректная дата: $date");
		}
		return (int) abs(round(($targetDate->getTimestamp() - $now->getTimestamp()) / 60));
	}
}
