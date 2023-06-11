<?

namespace php2steblya\scripts;

use php2steblya\File;
use php2steblya\Logger;

class TildaOrderWebhook
{
	public $log;
	private $site;
	private array $filePaths;
	public function __construct($site)
	{
		$this->site = $site;
	}
	public function init(): void
	{
		$this->log = new Logger('tilda orders webhook');
		$this->filePaths = [
			'orderLast.txt' => dirname(__FILE__) . '/TildaOrderLast_' . $this->site . '.txt',
			'orders.txt' => dirname(__FILE__) . '/TildaOrders_' . $this->site . '.txt',
		];

		$this->appendOrderToFile();
		$this->orderLast();
	}
	private function appendOrderToFile()
	{
		$ordersFile = new File($this->filePaths['orders.txt']);
		$orders = $ordersFile->getContents();
		if ($orders) {
			$orders = json_decode($orders, true);
		} else {
			$orders = [];
		}
		$orders[] = $_POST;
		$ordersFile->write(json_encode($orders));
	}
	private function orderLast()
	{
		$orderLastFile = new File($this->filePaths['orderLast.txt']);
		$orderLastFile->write(print_r($_POST, true));
		$products = [];
		foreach ($_POST['payment']['products'] as $product) {
			$products[] = $product['name'];
		}
		$this->log->setRemark($_POST['name-zakazchika'] . ' / ' . implode(',', $products));
		$this->log->writeSummary();
	}
}
