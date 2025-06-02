<?php

namespace php2steblya\order;

class OrderData_phone
{
	public static function normalize($phone)
	{
		if (empty($phone)) return '';
		$phone = preg_replace('/[^\d+]/', '', $phone); // Удаляем все символы, кроме цифр и плюса
		if (strpos($phone, '8') === 0) $phone = '7' . substr($phone, 1); // Для российских номеров с 8
		else if (strpos($phone, '+8') === 0) $phone = '7' . substr($phone, 2); // Для российских номеров с +8
		if ($phone && strpos($phone, '+') !== 0 && strlen($phone) >= 10) $phone = '+' . $phone; // Добавляем "+" если номер не пустой и его длина достаточна
		return $phone;
	}

	public static function tenDigits($phone)
	{
		return substr($phone, -10);
	}
}
