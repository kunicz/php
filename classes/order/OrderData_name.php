<?

namespace php2steblya\order;

class OrderData_name
{
	public static function explode($name)
	{
		$fullName = [];
		$fio = explode(' ', $name);
		switch (count($fio)) {
			case 1:
				$fullName[0] = $fio[0];
				$fullName[1] = '';
				$fullName[2] = '';
				break;
			case 2:
				$fullName[0] = array_shift($fio);
				$fullName[1] = $fio[0];
				$fullName[2] = '';
				break;
			default:
				$fullName[1] = array_shift($fio);
				$fullName[2] = array_pop($fio);
				$fullName[0] = implode(' ', $fio);
		}
		return $fullName;
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
