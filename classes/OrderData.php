<?

namespace php2steblya;

use php2steblya\File;
use php2steblya\Logger;
use php2steblya\OrderData_items as Items;
use php2steblya\OrderData_comments as Comments;
use php2steblya\OrderData_payments as Payments;
use php2steblya\OrderData_dostavka as Dostavka;
use php2steblya\OrderData_zakazchik as Zakazchik;
use php2steblya\OrderData_analytics as Analytics;
use php2steblya\OrderData_promocode as Promocode;
use php2steblya\OrderData_poluchatel as Poluchatel;

class OrderData
{
	public $log;
	public object $zakazchik;
	public object $poluchatel;
	public object $dostavka;
	public object $comments;
	public object $items;
	public object $payments;
	public object $analytics;
	public $cardText;
	public $customerId;
	public $promocode;
	public $status;
	public array $customFields;

	public function __construct($site)
	{
		$this->log = new Logger();
		$this->poluchatel = new Poluchatel();
		$this->zakazchik = new Zakazchik();
		$this->items = new Items($site);
		$this->payments = new Payments();
		$this->dostavka = new Dostavka();
		$this->comments = new Comments();
		$this->comments->setFlorist('');
		$this->comments->setCourier('');
		$this->analytics = new Analytics();
		$this->promocode = new Promocode();
		$this->status = 'new';
		$this->customFields = [];
		$this->customerId = '';
	}

	public function fromTilda(array $orderFromTilda)
	{
		// получатель		
		$this->poluchatel->setName($orderFromTilda['name-poluchatelya']);
		$this->poluchatel->setPhone($orderFromTilda['phone-poluchatelya']);
		// заказчик		
		$this->zakazchik->setName($orderFromTilda['name-zakazchika']);
		$this->zakazchik->setPhone($orderFromTilda['phone-zakazchika']);
		$this->zakazchik->setTelegram($orderFromTilda['messenger-zakazchika']);
		if ($orderFromTilda['uznat-adres-u-poluchatelya']) $this->zakazchik->znaetAdres(false);
		if ($orderFromTilda['onanim']) $this->zakazchik->onanim(true);
		if ($this->zakazchik->phone == $this->poluchatel->phone) $this->zakazchik->poluchatel(true);
		//товары		
		$this->items->fromTilda($orderFromTilda['payment']['products']);
		//платежи
		$this->payments->fromTilda($orderFromTilda['payment']);
		//доставка		
		$this->dostavka->setCity($orderFromTilda['adres-poluchatelya-city']);
		$this->dostavka->setStreet($orderFromTilda['adres-poluchatelya-street']);
		$this->dostavka->setBuilding($orderFromTilda['adres-poluchatelya-dom']);
		$this->dostavka->setHousing($orderFromTilda['adres-poluchatelya-korpus']);
		$this->dostavka->setHouse($orderFromTilda['adres-poluchatelya-stroenie']);
		$this->dostavka->setFlat($orderFromTilda['adres-poluchatelya-kvartira']);
		$this->dostavka->setFloor($orderFromTilda['adres-poluchatelya-etazh']);
		$this->dostavka->setBlock($orderFromTilda['adres-poluchatelya-podezd']);
		$this->dostavka->setDomofon($orderFromTilda['adres-poluchatelya-domofon']);
		$this->dostavka->setDate($orderFromTilda['dostavka-date']);
		$this->dostavka->setInterval($orderFromTilda['dostavka-interval']);
		$this->dostavka->setCost($orderFromTilda['dostavka-price']);
		$this->dostavka->setCode('courier');
		$this->dostavka->setAuto($this->items);
		//комменты		
		$this->comments->setFlorist(urldecode($orderFromTilda['florist-comment']));
		$courierComment = [];
		if ($this->dostavka->domofon) $courierComment[] = 'Код домофона: ' . $this->dostavka->domofon;
		if ($orderFromTilda['courier-comment']) $courierComment[] = urldecode($orderFromTilda['courier-comment']);
		$this->comments->setCourier(implode("\r\n", $courierComment));
		//аналитика		
		$this->analytics->setOtkudaUznal($orderFromTilda['otkuda-uznal-o-nas']);
		$this->analytics->setUtmSource($orderFromTilda['utm_source']);
		$this->analytics->setUtmCampaign($orderFromTilda['utm_campaign']);
		$this->analytics->setUtmMedium($orderFromTilda['utm_medium']);
		$this->analytics->setUtmContent($orderFromTilda['utm_content']);
		$this->analytics->setUtmTerm($orderFromTilda['utm_term']);
		$this->analytics->setYandexClientId($orderFromTilda['ya-client-id']);
		//промокод
		$this->promocode->setName($orderFromTilda['payment']['promocode']);
		$this->promocode->setAmount($orderFromTilda['payment']['discount']);
		//другое
		$this->customerId = $orderFromTilda['customerId'];
		$this->cardText = $orderFromTilda['text-v-kartochku'];

		$this->isCastrated();
	}

	public function getCrm($readyToApi = true)
	{
		$order = [
			'externalId' => 'php_' . time() . uniqid(),
			'orderMethod' => 'php',
			'status' => $this->status,
			'firstName' => $this->zakazchik->firstName,
			'lastName' => $this->zakazchik->lastName,
			'patronymic' => $this->zakazchik->patronymic,
			'phone' => $this->zakazchik->phone,
			'customerComment' => $this->comments->courier,
			'managerComment' => $this->comments->florist,
			'delivery' => [
				'code' => $this->dostavka->code,
				'address' => [
					'text' => $this->dostavka->getAdresText()
				],
				'date' => $this->dostavka->date,
				'time' => [
					'custom' => $this->dostavka->interval
				],
				'cost' => $this->dostavka->cost,
				'netCost' => $this->dostavka->netCost
			],
			'items' => $this->items->getCrm(),
			'payments' => $this->payments->getCrm(),
			'customFields' => [
				'card' => $this->items->getCards(),
				'text_v_kartochku' => $this->cardText,
				'onanim' => $this->zakazchik->onanim,
				'name_poluchatelya' => $this->poluchatel->name,
				'bukety_v_zakaze' => $this->items->getBukets(),
				'phone_poluchatelya' => $this->poluchatel->phone,
				'messenger_zakazchika' => $this->zakazchik->telegram,
				'otkuda_o_nas_uznal' => $this->analytics->otkudaUznal,
				'stoimost_dostavki_iz_tildy' => $this->dostavka->cost,
				'adres_poluchatelya' => $this->dostavka->getAdresText(),
				'zakazchil_poluchatel' => $this->zakazchik->poluchatel,
				'ya_client_id_order' => $this->analytics->yandex['clientId'],
				'uznat_adres_u_poluchatelya' => !$this->zakazchik->znaetAdres
			],
			'source' => [
				'keyword' => $this->analytics->utm['term'],
				'source' => $this->analytics->utm['source'],
				'medium' => $this->analytics->utm['medium'],
				'content' => $this->analytics->utm['content'],
				'campaign' => $this->analytics->utm['campaign']
			]
		];
		if ($this->customerId) {
			$order['customer'] = [
				'id' => $this->customerId
			];
		}
		foreach ($this->customFields as $key => $value) {
			$order['customFields'][$key] = $value;
		}
		if (!$readyToApi) return $order;
		return json_encode($order);
	}

	public function setCustomerId($data)
	{
		$this->customerId = $data;
	}

	public function setStatus($data)
	{
		$this->status = $data;
	}

	public function setCardText($text)
	{
		$this->cardText = $text;
	}

	public function addCustomField($key, $value)
	{
		$this->customFields[$key] = $value;
	}

	private function isCastrated()
	{
		if (!in_array($this->items->get()[0]->name, castrated_items())) return;
		$this->status = 'complete';
		//$this->comments->setFlorist('');
		$this->comments->setCourier('');
		$this->dostavka->setNetCost(0);
		$this->dostavka->setCost(0);
		$this->dostavka->setCity('');
		$this->dostavka->setStreet('');
		$this->dostavka->setBuilding('');
		$this->dostavka->setHousing('');
		$this->dostavka->setHouse('');
		$this->dostavka->setFlat('');
		$this->dostavka->setFloor('');
		$this->dostavka->setBlock('');
		$this->dostavka->setDomofon('');
		$this->dostavka->setInterval('');
		$this->poluchatel->setName('');
		$this->poluchatel->setPhone('');
		$this->zakazchik->onanim(false);
		$this->zakazchik->znaetAdres(true);
		$this->addCustomField('florist', 'boss');
		$this->cardText = '';
	}
}
