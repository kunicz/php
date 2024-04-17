<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\Finish;
use php2steblya\retailcrm\Response_orders_get;
use php2steblya\retailcrm\Response_orders_edit_post;

class RetailCrm_add_transport extends Script
{

	private $orderId;
	private $order;

	public function __construct($scriptData = [])
	{
		$this->logger = Logger::getInstance();
		$this->logger->addToLog('script', __CLASS__);
		try {
			if (!isset($scriptData['id'])) throw new \Exception("product id not set");
			$this->orderId = $scriptData['id'];
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	public function init()
	{
		try {
			$this->getOrderFromCrm();
			$this->addTransportItemToOrder();
			Finish::success();
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	private function getOrderFromCrm()
	{
		$args = [
			'filter' => [
				'ids' => [$this->orderId],
			]
		];
		$response = new Response_orders_get();
		$response->getOrdersFromCrm($args);
		if ($response->hasError()) throw new \Exception($response->getError());
		$orders = $response->getOrders();
		if (empty($orders)) throw new \Exception("order ($this->orderId) is not exist");
		$this->order = $orders[0];
	}

	private function addTransportItemToOrder()
	{
		//чтобы в заказ добавлялось транспортировчное, пришлось создать товар "транспортировочное" в каждом сайте
		//потому что срм через апи не умеет добавлять в заказ товары из других магазинов (в нашем случае, остатки)
		//добавляит просто товар без привязки к магазину также нельзя, так как будет возникать ошибка бронирования
		//для того, чтобы для разных городов и разных сайтоы выводилось свое транспортировочное, создал массив в котором
		//главный ключ - это id менеджера (Москва, бывшая Анна), а значение - массив с данными, которые могут отличаться у разных городов и магазинов
		$tenasportItems = [
			//москва
			13 => [
				'price' => 600,
				'id' => [
					'2steblya' => 3970,
					'staytrueflowers' => 3971
				]
			]
		];
		$items = $this->order->items;
		$items[] = [
			'initialPrice' => $tenasportItems[$this->order->managerId]['price'],
			'purchasePrice' => $tenasportItems[$this->order->managerId]['price'],
			'quantity' => 1,
			'offer' => ['id' => $tenasportItems[$this->order->managerId]['id'][$this->order->site]]
		];
		$args = [
			'by' => 'id',
			'site' => $this->order->site,
			'order' => json_encode(['items' => $items])
		];
		$response = new Response_orders_edit_post($this->orderId);
		$response->editOrderInCrm($args);
		if ($response->hasError()) throw new \Exception($response->getError());
	}
}
