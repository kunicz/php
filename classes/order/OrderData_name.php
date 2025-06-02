<?php

namespace php2steblya\order;

class OrderData_name
{
	public static function explode($name)
	{
		if (trim($name) === '') return ['', '', ''];

		$parts = preg_split('/\s+/', trim($name));
		$parts = array_filter($parts, fn($p) => $p !== ''); // убираем пустые
		$parts = array_values($parts); // нормализуем индексы

		$firstName = '';
		$lastName = '';
		$patronymic = '';

		// Отдельно определим возможное отчество
		$patronymicIndex = null;
		foreach ($parts as $i => $part) {
			// Пропускаем слова, в которых нет кириллицы
			if (!preg_match('/\p{Cyrillic}/u', $part)) continue;

			// Проверяем суффиксы отчеств
			if (preg_match('/(вич|вна)$/ui', $part)) {
				$patronymicIndex = $i;
				break;
			}
		}

		if ($patronymicIndex !== null) {
			$patronymic = $parts[$patronymicIndex];
			unset($parts[$patronymicIndex]);
			$parts = array_values($parts); // переиндексация

			if (count($parts) === 2) {
				$firstName = $parts[0];
				$lastName = $parts[1];
			} elseif (count($parts) === 1) {
				$firstName = $parts[0];
			}
		} else {
			$count = count($parts);
			if ($count === 1) {
				$firstName = $parts[0];
			} elseif ($count === 2) {
				$firstName = $parts[0];
				$lastName = $parts[1];
			} elseif ($count >= 3) {
				$firstName = $parts[0];
				$lastName = implode(' ', array_slice($parts, 1));
			}
		}

		return [$firstName, $lastName, $patronymic];
	}

	public static function implode($firstName = '', $lastName = '', $patronymic = '')
	{
		$fio = '';
		if ($lastName) $fio .= $lastName;
		if ($firstName) $fio .= ' ' . $firstName;
		if ($patronymic) $fio .= ' ' . $patronymic;
		return trim($fio);
	}
}
