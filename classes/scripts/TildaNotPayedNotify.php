<?

namespace php2steblya\scripts;

use php2steblya\File;
use php2steblya\Logger;
use php2steblya\TelegramBot;

class TildaNotPayedNotify
{
	public $log;
	private $source;

	public function init()
	{
		$this->source = 'unpayed order notify';
		$this->log = new Logger($this->source);
		$now = time();
		foreach (allowed_sites() as $site) {
			$this->log->insert($site);
			$file = new File(dirname(dirname(dirname(__FILE__))) . '/TildaOrdersNotPayed_' . $site . '.txt');
			$orders = json_decode($file->getContents(), true);
			if (empty($orders)) continue;
			$ordersToDelete = [];
			foreach ($orders as $key => $order) {
				$orderTime = strtotime($order['date']);
				$orderLog = [
					'time' => [
						'now' => $now,
						'order' => $orderTime
					],
					'postData' => $order['orderData']
				];
				if ($orderTime > $now - (30 * 60)) continue; // если заказ записан менее чем полчаса назад
				$telegramBot = new TelegramBot($order);
				$orderLog['telegram'] = $telegramBot->getLog();
				$this->log->push($order['payment']['orderId'], $orderLog);
				$ordersToDelete[] = $key;
			}
			foreach ($ordersToDelete as $key) {
				unset($orders[$key]);
			}
			$file->write(json_encode($orders));
		}
	}
}
