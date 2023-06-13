<?

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\OrderData_items as Items;
use php2steblya\LoggerException as Exception;
use php2steblya\OrderData_comments as Comments;
use php2steblya\OrderData_payments as Payments;
use php2steblya\OrderData_dostavka as Dostavka;
use php2steblya\OrderData_zakazchik as Zakazchik;
use php2steblya\OrderData_analytics as Analytics;
use php2steblya\OrderData_poluchatel as Poluchatel;

class OrderData
{
	public $log;
	private $site;
	public object $zakazchik;
	public object $poluchatel;
	public object $dostavka;
	public object $comments;
	public object $items;
	public object $payments;
	public object $analytics;
	private $cardText;
	private $customerId;
	private $status;
	private array $customFields;

	public function __construct($site)
	{
		$this->log = new Logger();
		$this->setSite($site);
		$this->poluchatel = new Poluchatel();
		$this->zakazchik = new Zakazchik();
		$this->items = new Items($site);
		$this->payments = new Payments();
		$this->dostavka = new Dostavka();
		$this->comments = new Comments();
		$this->comments->setFlorist('');
		$this->comments->setCourier('');
		$this->analytics = new Analytics();
		$this->status = 'new';
		$this->customFields = [];
	}
	public function fromTilda(array $orderFromTilda)
	{
		// получатель		
		$this->poluchatel->setName($orderFromTilda['name-poluchatelya']);
		$this->poluchatel->setPhone($orderFromTilda['phone-poluchatelya']);
		// заказчик		
		$this->zakazchik->setName($orderFromTilda['name-zakazchika']);
		$this->zakazchik->setPhone($orderFromTilda['phone-zakazchika']);
		$this->zakazchik->setMesenger($orderFromTilda['messenger-zakazchika']);
		$this->zakazchik->znaetAdres($orderFromTilda['uznat-adres-u-poluchatelya']);
		if ($orderFromTilda['onanim']) $this->zakazchik->onanim();
		if ($this->zakazchik->phone == $this->poluchatel->phone) $this->zakazchik->poluchatel();
		//товары		
		$this->items->fromTilda($orderFromTilda['payment']['products']);
		$this->addCustomField('bukety_v_zakaze', $this->items->getBukets());
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
		$this->dostavka->setAuto($this->items);
		//комменты		
		$this->comments->setFlorist($orderFromTilda['florist-comment']);
		$this->comments->setCourier($orderFromTilda['courier-comment']);
		//дополнительные поля
		$this->cardText = $orderFromTilda['text-v-kartochku'];
		//аналитика		
		$this->analytics->setOtkudaUznal($orderFromTilda['otkuda-uznal-o-nas']);
		$this->analytics->setUtmSource($orderFromTilda['utm_source']);
		$this->analytics->setUtmCampaign($orderFromTilda['utm_campaign']);
		$this->analytics->setUtmMedium($orderFromTilda['utm_medium']);
		$this->analytics->setUtmContent($orderFromTilda['utm_content']);
		$this->analytics->setUtmTerm($orderFromTilda['utm_term']);
		$this->analytics->setYandexClientId($orderFromTilda['ya-client-id']);
	}
	public function getCrm($readyToApi = true)
	{
		$order = [
			'externalId' => 'php_' . time(),
			'orderMethod' => 'php',
			'status' => $this->status,
			'customer' => ['id' => $this->customerId],
			'firstName' => $this->zakazchik->firstName,
			'lastName' => $this->zakazchik->lastName,
			'patronymic' => $this->zakazchik->patronymic,
			'phone' => $this->zakazchik->phone,
			'customerComment' => $this->comments->courier,
			'managerComment' => $this->comments->florist,
			'delivery' => [
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
				'onanim' => $this->zakazchik->isOnanim(),
				'name_poluchatelya' => $this->poluchatel->name,
				'bukety_v_zakaze' => $this->items->getBukets(),
				'phone_poluchatelya' => $this->poluchatel->phone,
				'otkuda_o_nas_uznal' => $this->analytics->otkudaUznal,
				'messenger-zakazchika' => $this->zakazchik->telegram,
				'stoimost_dostavki_iz_tildy' => $this->dostavka->cost,
				'adres_poluchatelya' => $this->dostavka->getAdresText(),
				'zakazchil_poluchatel' => $this->zakazchik->isPoluchatel(),
				'ya_client_id_order' => $this->analytics->yandex['clientId']
			],
			'source' => [
				'keyword' => $this->analytics->utm['term'],
				'source' => $this->analytics->utm['source'],
				'medium' => $this->analytics->utm['medium'],
				'content' => $this->analytics->utm['content'],
				'campaign' => $this->analytics->utm['campaign']
			]
		];
		foreach ($this->customFields as $key => $value) {
			$order['customFields'][$key] = $value;
		}
		if (!$readyToApi) return $order;
		return json_encode($order);
	}
	public function getSite()
	{
		return $this->site;
	}
	public function setSite($data)
	{
		try {
			$this->log->push('site', $data);
			if (!in_array($data, allowed_sites())) {
				throw new Exception('site "' . $data . '" is not allowed');
			}
			$this->site = $data;
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
	public function getCustomerId()
	{
		return $this->customerId;
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
}
