<?

namespace php2steblya;

use php2steblya\OrderData_name as Name;
use php2steblya\OrderData_zakazchik_telegram as Telegram;

class OrderData_zakazchik
{
	public $firstName;
	public $lastName;
	public $patronymic;
	public $telegram;
	public $phone;
	public bool $onanim;
	public bool $poluchatel;
	public bool $znaetAdres;

	public function __construct()
	{
		$this->onanim = false;
		$this->poluchatel = false;
		$this->znaetAdres = true;
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
	public function setTelegram($data)
	{
		$telegram = new Telegram($data);
		$this->telegram = $telegram->get();
	}
	public function onanim(bool $data)
	{
		$this->onanim = $data;
	}
	public function poluchatel(bool $data)
	{
		$this->poluchatel = $data;
	}
	public function znaetAdres(bool $data)
	{
		$this->znaetAdres = $data;
	}
}
