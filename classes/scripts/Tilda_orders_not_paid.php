<?

namespace php2steblya\scripts;

use php2steblya\Finish;
use php2steblya\utils\DateTime;
use php2steblya\order\OrderData_telegram;
use php2steblya\telegram\Response_sendMessage_post;

class Tilda_orders_not_paid extends Script
{
	private $orderData;

	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			$this->logger->addToLog('now', date('Y-m-d H:i:s'));
			foreach ($this->getSitesFromDB('id') as $shop_crm_id) {
				$unpaidOrdersFromDB = $this->db->sql("SELECT * FROM orders WHERE shop_crm_id = '{$shop_crm_id}' AND paid = '0'");
				if (empty($unpaidOrdersFromDB)) continue;
				foreach ($unpaidOrdersFromDB as $i => $unpaidOrder) {
					$this->logger->addToLog($i . '_unpaid_order_from_DB', $unpaidOrder);
					$this->logger->addToLog($i . '_unpaid_order_createdOn', $unpaidOrder->createdOn);

					// если заказ записан менее чем полчаса назад
					$minutesPassed = abs(DateTime::calculateMinutesFromNowTo($unpaidOrder->createdOn));
					$this->logger->addToLog($i . '_time_difference', $minutesPassed);
					if ($minutesPassed <= 30) continue;

					$this->orderData = json_decode($unpaidOrder->order_data, true);

					//ищем оплаченный заказ для неоплаченного
					$paidOrder = $this->db->sql("SELECT 1 FROM orders WHERE paid = 1 AND shop_crm_id = '{$shop_crm_id}' AND tilda_order_id='{$unpaidOrder->tilda_order_id}'");
					$this->logger->addToLog($i . '_paid_order_from_DB', $paidOrder);
					if (empty($paidOrder)) {
						//отправляем сообщение
						$telegramChannel = $this->telegramChannel();
						if (!$telegramChannel) continue;
						$args = [
							//'chat_id' => $_ENV['telegram_admin_chat_id'],
							'chat_id' => $telegramChannel,
							'parse_mode' => 'HTML',
							'text' => OrderData_telegram::getMessageForChannel($this->orderData)
						];
						$response = new Response_sendMessage_post('orders');
						$response = $response->sendMessage($args);
						if (!$response->ok) continue;
					}

					//удаляем заказ из базы
					$this->db->sql("DELETE FROM orders WHERE db_id = '{$unpaidOrder->db_id}'");
				}
			}
			Finish::success();
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	private function telegramChannel()
	{
		$telegraChannel = $this->db->sql("
			SELECT telegram_id
			FROM telegram_channels 
			WHERE city_id = '{$this->orderData['city_id']}' AND name = 'orders_unpaid' AND shop_crm_id = '{$this->orderData['shop_crm_id']}'
		");
		if (empty($telegraChannel)) throw new \Exception("channel (name: orders_unpaid, city_id: {$this->orderData['city_id']}, shop_crm_id: {$this->orderData['shop_crm_id']}) not found in DB");
		return $telegraChannel[0]->telegram_id;
	}
}
