<?

namespace php2steblya;

class OrderData_name
{
	private $firstName;
	private $lastName;
	private $patronymic;

	public function __construct($name)
	{
		$fio = explode(' ', $name);
		switch (count($fio)) {
			case 1:
				$this->firstName = $fio[0];
				$this->lastName = '';
				$this->patronymic = '';
				break;
			case 2:
				$this->firstName = array_shift($fio);
				$this->lastName = $fio[0];
				$this->patronymic = '';
				break;
			default:
				$this->lastName = array_shift($fio);
				$this->patronymic = array_pop($fio);
				$this->firstName = implode(' ', $fio);
		}
	}
	public function getFirstName()
	{
		return $this->firstName;
	}
	public function getLastName()
	{
		return $this->lastName;
	}
	public function getPatronymic()
	{
		return $this->patronymic;
	}
}
