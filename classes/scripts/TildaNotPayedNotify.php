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
		$filesPaths = [
			'2steblya' => dirname(dirname(dirname(__FILE__))) . '/TildaOrdersNotPayed_2steblya.txt',
			'staytrueflowers' => dirname(dirname(dirname(__FILE__))) . '/TildaOrdersNotPayed_staytrueflowers.txt'
		];
		foreach ($filesPaths as $site => $path) {
			$this->log->insert($site);
			$file = new File($path);
			$orders = json_decode($file->getContents(), true);
			if (empty($orders)) continue;
			for ($i = 0; $i < count($orders); $i++) {
				$orderTime = strtotime($orders[$i]['date']);
				$orderLog = [
					'time' => [
						'now' => $now,
						'order' => $orderTime
					],
					'postData' => $orders[$i]['orderData']
				];
				if ($orderTime > $now - (30 * 60)) continue; // если заказ записан менее чем полчаса назад
				$telegramBot = new TelegramBot($orders[$i]);
				$orderLog['telegram'] = $telegramBot->getLog();
				$this->log->push($orders[$i]['payment']['orderId'], $orderLog);
				unset($orders[$i]);
			}
			$file->write(json_encode($orders));
		}
	}
}
