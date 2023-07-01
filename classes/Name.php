<?

namespace php2steblya;

class Name
{
	private $name;

	public function __construct($firstName, $lastName, $patronymic)
	{
		$this->name = '';
		if ($lastName) $this->name .= $$lastName;
		if ($firstName) $this->name .= ' ' . $firstName;
		if ($patronymic) $this->name .= ' ' . $patronymic;
	}
	public function getName()
	{
		return trim($this->name);
	}
}
