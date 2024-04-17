<?

namespace php2steblya\order;

use php2steblya\Db;
use php2steblya\Logger;
use php2steblya\order\OrderData;
use php2steblya\order\OrderData_telegram;
use php2steblya\telegram\Response_sendMessage_post;
use php2steblya\retailcrm\Response_orders_create_post;

class Order
{
	private $db;
	private $logger;
	private array $orderData;

	public function __construct($orderData = [])
	{
		$this->db = DB::getInstance();
		$this->logger = Logger::getInstance();
		$this->orderData = OrderData::prepare($orderData);
		$this->logger->addToLog('orderData_prepared', $this->orderData);
	}

	public function sendToTelegramChannel()
	{
		try {
			$paid = $this->orderData['paid'] ? 'paid' : 'unpaid';
			$telegramChannel = $this->db->sql("
				SELECT telegram_id
				FROM telegram_channels 
				WHERE city_id = '{$this->orderData['city_id']}' AND name = 'orders_$paid' AND shop_crm_id = '{$this->orderData['shop_crm_id']}'
			");
			if (empty($telegramChannel)) {
				throw new \Exception("channel (name: orders_$paid, city_id: {$this->orderData['city_id']}, shop_crm_id: {$this->orderData['shop_crm_id']}) not found in DB");
				return;
			}
			$args = [
				//'chat_id' => $_ENV['telegram_admin_chat_id'],
				'chat_id' => $telegramChannel[0]->telegram_id,
				'parse_mode' => 'HTML',
				'text' => OrderData_telegram::getMessageForChannel($this->orderData)
			];
			$response = new Response_sendMessage_post('orders');
			$response = $response->sendMessage($args);
			return $response->result->message_id;
		} catch (\Exception $e) {
			$this->logger->addToLog('error_message', $e->getMessage());
			$this->logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			$this->logger->sendToAdmin();
		}
	}

	public function sendToCrm()
	{
		$orders = OrderData::getOrdersWithSeparatedProducts($this->orderData);
		$this->logger->addToLog('orders_separated_for_crm', $orders);
		foreach ($orders as $key => $order) {
			$orderData = OrderData::getCrmArgs($order);
			$this->logger->addToLog('crm_args_for_order_' . $key, $orderData);
			$args = [
				'site' => $order['site'],
				'order' => json_encode($orderData)
			];
			$response = new Response_orders_create_post();
			$response->createOrderInCRM($args);
		}
	}

	public function saveToDB()
	{
		$params = [
			'shop_crm_id' => $this->orderData['shop_crm_id'],
			'paid' => $this->orderData['paid'],
			'order_data' => json_encode($this->orderData),
			'product_name' => $this->getProductsNames(),
			'customer_name' => $this->orderData['name_zakazchika'],
			'tilda_order_id' => $this->orderData['payment']['orderid']
		];
		$this->db->sql(
			"INSERT INTO orders (shop_crm_id, paid, order_data, product_name, customer_name, tilda_order_id) 
			VALUES (:shop_crm_id, :paid, :order_data, :product_name, :customer_name, :tilda_order_id)",
			$params
		);
	}

	private function getProductsNames()
	{
		if (!isset($this->orderData['payment']['products'])) return '';
		$products = [];
		foreach ($this->orderData['payment']['products'] as $product) {
			$products[] = $product['name'];
		}
		return implode(', ', $products);
	}
}
