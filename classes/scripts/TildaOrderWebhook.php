<?

namespace php2steblya\scripts;

use php2steblya\File;
use php2steblya\Logger;
use php2steblya\OrderData;
use php2steblya\TelegramBot;
use php2steblya\ApiRetailCrmResponse_customers_get as Customers_get;
use php2steblya\ApiRetailCrmResponse_orders_create as Orders_create;

class TildaOrderWebhook
{
	public $log;
	private $site;
	private $payed;
	private $source;
	private array $postData;
	private array $filePaths;
	private array $ordersIds;

	public function __construct(string $site, bool $payed = false, bool $testMode = false)
	{
		$this->site = $site;
		$this->payed = $payed;
		$this->source = 'tilda orders webhook';
		$this->log = new Logger($this->source);
		$this->filePaths = [
			'orderTest.json' => dirname(dirname(dirname(__FILE__))) . '/testOrder.json',
			'orders.txt' => dirname(dirname(dirname(__FILE__))) . '/TildaOrders_' . $site . '.txt',
			'orderLast.txt' => dirname(dirname(dirname(__FILE__))) . '/TildaOrderLast_' . $site . '.txt',
			'notPayed.txt' => dirname(dirname(dirname(__FILE__))) . '/TildaOrdersNotPayed_' . $site . '.txt',
		];
		if ($testMode) {
			$testOrderFile = new File($this->filePaths['orderTest.json']);
			$this->postData = json_decode($testOrderFile->getContents(), true);
			$this->log->push('isOrderTest', true);
		} else {
			$this->postData = $_POST;
		}
		$this->postData['customerId'] = null;
		$this->postData['site'] = $this->site;
		$this->postData['payed'] = $this->payed;
		$this->postData['date'] = date('Y-m-d H:i:s');
		$this->log->push('postData', $this->postData);
		$this->orderLastToFile();
		$this->appendOrderToFile();
	}
	public function init()
	{
		if (!$this->isOrderReal()) return;
		$this->isOrderPayed() ? $this->orderPayed() : $this->orderUnpayed();
		$this->log->writeSummary();
	}
	private function isOrderReal(): bool
	{
		$conditions = [
			isset($this->postData['test']), // при привязке вебхука тильда отправляет запрос с $_POST['test'=>'test']
			!isset($this->postData['formid']) // при удачном завершении заказа тильда отправляет массив, в котором всегда есть "formid"
		];
		if (in_array(true, $conditions)) {
			$this->log->push('isOrderReal', false);
			return false;
		} else {
			$this->log->push('isOrderReal', true);
			return true;
		}
	}
	private function isOrderPayed(): bool
	{
		if ($this->payed) {
			$this->log->push('isOrderPayed', true);
			return true;
		} else {
			$this->log->push('isOrderPayed', false);
			return false;
		}
	}
	private function orderUnpayed()
	{
		/**
		 * если заказ не оплачен, заносим его postData в массив и сохраняем в файле
		 * cron в скрипте TildaNotPayedNotify.php раз в полчаса открывает файл и пробегается по массиву
		 * если заказ уже старше получаса - отправляет сообщение в канал 
		 */
		File::collect($this->filePaths['notPayed.txt'], $this->postData);
		$remark = 'recieved order (tilda: ' . $this->postData['payment']['orderid'] . ') | ' . $this->site;
		$this->log->setRemark($remark);
	}
	private function orderPayed()
	{
		/**
		 * если заказ оплачен, то:
		 * 1. отправляем сообщение в канал телеграмм
		 * 2. удаляем postData заказа из массива неоплаченных
		 * 3. для каждого товара в заказе создаем новый заказ в срм
		 */
		$this->log->insert('2. create orders');
		$this->sendTelegram();
		$this->removeFromUnpayed($this->postData['payment']['orderid']);
		$this->searchCustomer($this->postData['phone-zakazchika']);
		for ($i = 0; $i < count($this->postData['payment']['products']); $i++) { //для каждого товара в заказе создаем отдельный заказ в срм
			$postData = $this->postData;
			$postData['payment']['products'] = [$this->postData['payment']['products'][$i]];
			$postData['payment']['amount'] = $this->postData['payment']['products'][$i]['amount'];
			if ($postData['payment']['products'][$i]['name'] == 'подписка') { // тут будет правильное условие для товароыв из подписки
				//тут будет цикл, создающий заказы по подписке
			} else {
				$orderData = new OrderData($this->site);
				$orderData->fromTilda($postData);
				$this->createOrder($orderData);
			}
		}
		$products = [];
		foreach ($this->postData['payment']['products'] as $product) {
			$products[] = $product['name'];
		}
		$remark = 'created order' . (count($this->ordersIds) > 1 ? 's' : '');
		$remark .= ' (tilda: ' . $this->postData['payment']['orderid'] . ', crm: ' . implode(',', $this->ordersIds) . ')';
		$remark .= ' for ' . $this->postData['name-zakazchika'] . ($this->postData['customerId'] ? ' (' . $this->postData['customerId'] . ')' : '');
		$remark .= ', products: ' . implode(', ', $products);
		$remark .= ' | ' . $this->site;
		$this->log->setRemark($remark);
	}
	private function sendTelegram()
	{
		$telegramBot = new TelegramBot($this->postData);
		$this->log->push('telegram', $telegramBot->getLog());
	}
	private function createOrder($orderData)
	{
		$args = [
			'site' => $this->site,
			'order' => $orderData->getCrm()
		];
		$order = new Orders_create($this->source, $args);
		$this->ordersIds[] = $order->getId();
		$this->log->push($order->getId(), $order->getLog());
	}
	private function searchCustomer($phone)
	{
		$args = [
			'filter' => [
				'name' => $phone
			]
		];
		$customer = new Customers_get($this->source, $args, $phone);
		$this->log->push('1. search customer', $customer->getLog());
		if (!$customer->has()) return;
		$this->postData['customerId'] = $customer->getIds()[0];
	}
	private function appendOrderToFile()
	{
		File::collect($this->filePaths['orders.txt'], $this->postData);
	}
	private function orderLastToFile()
	{
		$orderLastFile = new File($this->filePaths['orderLast.txt']);
		$orderLastFile->write(print_r($this->postData, true));
	}
	private function removeFromUnpayed($data)
	{
		$file = new File($this->filePaths['notPayed.txt']);
		$orders = json_decode($file->getContents(), true);
		for ($i = 0; $i < count($orders); $i++) {
			if ($orders[$i]['payment']['orderid'] != $data) continue;
			unset($orders[$i]);
			break;
		}
		$file->write(json_encode($orders));
	}
}
