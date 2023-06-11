<?

namespace php2steblya;

use php2steblya\OrderData_name as Name;

class OrderData_zakazchik
{
	public $firstName;
	public $lastName;
	public $patronymic;
	public array $messenger;
	public $phone;
	private bool $onanim;
	private bool $poluchatel;
	private bool $znaetAdres;

	public function __construct()
	{
		$this->onanim = false;
		$this->poluchatel = false;
	}
	public function setName($data)
	{
		$name = new Name($data);
		$this->firstName = $name->getFirstName();
		$this->lastName = $name->getLastName();
		$this->patronymic = $name->getPatronymic();
	}
	public function setFirstName($data)
	{
		$this->firstName = $data;
	}
	public function setLastName($data)
	{
		$this->lastName = $data;
	}
	public function setPatronymic($data)
	{
		$this->patronymic = $data;
	}
	public function setPhone($data)
	{
		$this->phone = $data;
	}
	public function setMesenger($telegram)
	{
		$this->messenger[0]['vendor'] = 'telegram';
		$this->messenger[0]['value'] = $telegram;
	}
	public function onanim()
	{
		$this->onanim = true;
	}
	public function poluchatel()
	{
		$this->poluchatel = true;
	}
	public function znaetAdres($data = false)
	{
		$this->znaetAdres = $data ? true : false;
	}
	public function isOnanim()
	{
		return $this->onanim;
	}
	public function isPoluchatel()
	{
		return $this->poluchatel;
	}
	public function isZnaetAdres()
	{
		return $this->znaetAdres;
	}
}
