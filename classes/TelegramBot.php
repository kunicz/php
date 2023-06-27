<?

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiTelegram;
use php2steblya\OrderData_item_sku as Sku;

class TelegramBot
{
	public $log;
	private $site;
	private $payed;
	private $source;
	private $channel;
	private $postData;
	private $orderData;
	private $message;

	public function __construct($postData)
	{
		$this->source = 'telegram bot';
		$this->log = new Logger($this->source);
		$this->site = $postData['site'];
		$this->payed = $postData['payed'];
		$this->postData = $postData;
		$this->orderData = new OrderData($this->site);
		$this->orderData->fromTilda($postData);
		$this->buildMessage();
		if ($this->payed) {
			$this->channel = $_ENV['telegram_' . $this->site . '_payed'];
		} else {
			$this->channel = $_ENV['telegram_' . $this->site . '_unpayed'];
		}
		$this->log->push('channel', $this->channel);
		$this->submit();
	}
	private function buildMessage()
	{
		$message = [];
		$message[] = '🙎‍♂️ <b>Заказчик:</b>';
		$telegram = $this->orderData->zakazchik->telegram ? ' @' . $this->orderData->zakazchik->telegram : '';
		$message[] = $this->postData['name-zakazchika'] . $telegram;
		$message[] = $this->orderData->zakazchik->phone;
		if ($this->orderData->zakazchik->onanim) $message[] = '<i>(аноним)</i>';
		if ($this->orderData->poluchatel->name || $this->orderData->poluchatel->phone) {
			$message[] = '🙎 <b>Получатель:</b>';
			if ($this->orderData->poluchatel->name) $message[] = $this->orderData->poluchatel->name;
			if ($this->orderData->poluchatel->phone) $message[] = $this->orderData->poluchatel->phone;
		}
		$message[] = '🏠 <b>Доставка:</b>';
		if ($this->postData['uznat-adres-u-poluchatelya']) {
			$message[] = 'узнать адрес у получателя';
		} else {
			if ($this->orderData->dostavka->getAdresText()) $message[] = $this->orderData->dostavka->getAdresText();
		}
		$message[] = date('d.m.Y', strtotime($this->orderData->dostavka->date)) . ' ' . $this->orderData->dostavka->interval;
		$products = [];
		foreach ($this->postData['payment']['products'] as $item) {
			$props = [];
			foreach ($item['options'] as $option) {
				switch ($option['option']) {
					case 'фор мат':
					case 'Размер':
						$props[0] = $option['variant'];
						break;
					case 'выебри карточку':
						$props[1] = $option['variant'];
						break;
				}
			}
			$sku = new Sku($item['sku']);
			$products[] = $item['name'] . ($sku->isVitrina() ? ' с витрины' : '') . ' (' . (!empty($props) ? implode(' ', $props) . ', ' : '') . $item['quantity'] . ' шт) - ' . $item['amount'] . ' р.';
		}
		$message[] = '🌸 <b>Товары:</b>';
		foreach ($products as $product) {
			$message[] = $product;
		}
		if ($this->orderData->comments->florist) {
			$message[] = '💬 <b>Комментарий флористу:</b>';
			$message[] = $this->orderData->comments->florist;
		}
		if ($this->orderData->comments->courier) {
			$message[] = '💬 <b>Комментарий курьеру:</b>';
			$message[] = $this->orderData->comments->courier;
		}
		if ($this->orderData->cardText) {
			$message[] = '💬 <b>Текст в карточку:</b>';
			$message[] = $this->orderData->cardText;
		}
		$message[] = '💵 <b>Сумма заказа:</b> ' . $this->postData['payment']['amount'] . ' р.';
		$message[] = '⏱ <b>Время заказа:</b> ' . date('d.m.Y H:i', strtotime($this->postData['date']));
		$message[] = $this->payed ? '✅ Оплачен' : '⛔️ Не оплачен';
		foreach ($this->orderData->analytics->utm as $key => $value) {
			if ($value) $message[] = '<b>utm-' . $key . ':</b> ' . $value;
		}
		$this->message = implode("\r\n", $message);
	}
	public function submit()
	{
		$args = [
			'chat_id' => $this->channel,
			'text' => $this->message,
			'parse_mode' => 'HTML'
		];
		$api = new ApiTelegram();
		$api->post('sendMessage', $args);
		if ($api->hasErrors()) {
			$this->log->pushError($api->getError());
		}
		$this->log->push('response', $api->response);
	}
	public function setChannel($data)
	{
		$this->channel = $data;
	}
	public function setSite($data)
	{
		$this->site = $data;
	}
	public function getLog()
	{
		return $this->log->get();
	}
}
