<?

namespace php2steblya;

use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\ApiTelegram;

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
		$message[] = $this->postData['name-zakazchika'] . ' @' . $this->orderData->zakazchik->telegram;
		$message[] = $this->orderData->zakazchik->phone;
		$message[] = '🙎 <b>Получатель:</b>';
		$message[] = $this->orderData->poluchatel->name;
		$message[] = $this->orderData->poluchatel->phone;
		$message[] = '🏠 <b>Доставка:</b>';
		if ($this->postData['uznat-adres-u-poluchatelya']) {
			$message[] = 'узнать адрес у получателя';
		} else {
			$message[] = $this->orderData->dostavka->getAdresText();
		}
		$message[] = date('d.m.Y', strtotime($this->orderData->dostavka->date)) . ' ' . $this->orderData->dostavka->interval;
		$message[] = '🌸 <b>Товары:</b>';
		$products = [];
		foreach ($this->postData['payment']['products'] as $item) {
			$products[] = $item['name'] . ' (' . $item['quantity'] . ' шт) - ' . $item['amount'] . ' р.';
		}
		$message[] = implode("\r\n", $products);
		$message[] = '💵 <b>Сумма заказа:</b> ' . $this->postData['payment']['amount'] . ' р.';
		$message[] = '⏱ <b>Время заказа:</b> ' . date('d.m.Y H:i', strtotime($this->postData['date']));
		$message[] = $this->payed ? '✅ Оплачен' : '⛔️ Не оплачен';
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
