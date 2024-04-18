<?

namespace php2steblya\scripts;

use php2steblya\Finish;
use php2steblya\retailcrm\Response_orders_get;
use php2steblya\retailcrm\Response_orders_edit_post;

class RetailCrm_add_transport extends Script
{

	private $orderId;
	private $order;

	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			if (!isset($this->scriptData['id']) || !$this->scriptData['id']) throw new \Exception("product id not set");
			$this->orderId = $this->scriptData['id'];

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
		//для того, чтобы для разных городов и разных сайтоы выводилось свое транспортировочное со своей ценой, создал массив в котором
		//нумерной ключ в ценах - это id менеджера
		//ids транспортировочных - это прямо магическое число. В самой срм его нигде не найти. Чтоб его получить, нужно отключить user_jscss в срм,
		//добавить в какой-то заказ транспортировочное нужного магазина, сохранить, а потом запросить этот заказ через апи (лучше всего делать это в Test.php)
		//в logger можно будет найти order -> 0 -> items -> offer -> id
		$tenasportItems = [
			13 => [ //москва
				'price' => 600,
				'ids' => [
					'2steblya' => 3970,
					'staytrueflowers' => 3971,
					'gvozdisco' => 4104
				]
			]

		];
		$items = $this->order->items;
		$items[] = [
			'initialPrice' => $tenasportItems[$this->order->managerId]['price'],
			'purchasePrice' => $tenasportItems[$this->order->managerId]['price'],
			'quantity' => 1,
			'offer' => ['id' => $tenasportItems[$this->order->managerId]['ids'][$this->order->site]]
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
