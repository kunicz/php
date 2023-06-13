<?

namespace php2steblya;

class OrderData_dostavka_adres
{
	private $region; //регион
	private $city; //город
	private $street; //улица
	private $building; //дом
	private $housing; //корпус
	private $house; //строение
	private $flat; //квартира
	private $floor; //этаж
	private $block; //подъезд
	private $domofon; //домофон

	public function getAdresText()
	{
		$adres = [];
		if ($this->region) $adres[] = $this->region;
		if ($this->city) $adres[] = 'г. ' . $this->city;
		if ($this->street) $adres[] = $this->street;
		if ($this->building) $adres[] = 'д. ' . $this->building;
		if ($this->housing) $adres[] = 'корп. ' . $this->housing;
		if ($this->house) $adres[] = 'стр. ' . $this->house;
		if ($this->flat) $adres[] = 'кв. ' . $this->flat;
		if ($this->block) $adres[] = 'подъезд ' . $this->block;
		if ($this->floor) $adres[] = 'этаж ' . $this->floor;
		return implode(', ', $adres);
	}
	public function getAdresArray()
	{
		return [
			'region' => $this->region,
			'city' => $this->city,
			'street' => $this->street,
			'building' => $this->building,
			'housing' => $this->housing,
			'house' => $this->house,
			'flat' => $this->flat,
			'floor' => $this->floor,
			'block' => $this->block,
			'domofon' => $this->domofon
		];
	}
	public function setRegion($data)
	{
		$this->region = $data;
	}
	public function setCity($data)
	{
		$this->city = $data;
	}
	public function setStreet($data)
	{
		$this->street = $data;
	}
	public function setBuilding($data)
	{
		$this->building = $data;
	}
	public function setHousing($data)
	{
		$this->housing = $data;
	}
	public function setHouse($data)
	{
		$this->house = $data;
	}
	public function setFlat($data)
	{
		$this->flat = $data;
	}
	public function setFloor($data)
	{
		$this->floor = $data;
	}
	public function setBlock($data)
	{
		$this->block = $data;
	}
	public function setDomofon($data)
	{
		$this->domofon = $data;
	}
}
