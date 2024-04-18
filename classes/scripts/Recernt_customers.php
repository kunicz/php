<?

namespace php2steblya\scripts;

use php2steblya\Finish;
use php2steblya\retailcrm\Response_orders_get;
use php2steblya\retailcrm\Response_customers_edit_post;
use php2steblya\order\OrderData_telegram;

class Recernt_customers extends Script
{
	private array $orders;

	public function init()
	{
		$this->logger->addToLog('script', __CLASS__);

		try {
			$this->collectOrders();
			$this->updateCustomers();
			Finish::success();
		} catch (\Exception $e) {
			Finish::fail($e);
		}
	}

	private function collectOrders()
	{
		$args = [
			'limit' => 100,
			'filter' => [
				'createdAtFrom' => date('Y-m-d', strtotime('-2 day')),
				'createdAtTo' => date('Y-m-d', strtotime('-1 day'))
			]
		];
		$response = new Response_orders_get();
		$response->getOrdersFromCrm($args);
		if ($response->hasError()) throw new \Exception($response->getError());
		$this->orders = $response->getOrders();

		$this->logger->addToLog('clearCustomerAdres_collect_orders', $response->getOrders());
		$this->logger->addToLog('clearCustomerAdres_collect_orders_args', $args);
	}

	private function updateCustomers()
	{
		if (empty($this->orders)) return;
		$i = 0;
		$loggerCustomersData = [];
		foreach ($this->orders as $order) {
			/**
			 * что нужно изменить в клиенте:
			 * 1. удалить адрес, чтоб он не вставлялся автоматически в следующие заказы
			 * 2. добавить ему телеграм из заказа (можно перезаписать старое значение)
			 * 3. добавить поле "откуда узнал" (если оно пустое)
			 * 4. добавить поле "Яндекс ID"
			 */
			//1
			$costumerArgs = [];

			if (isset($order->customer->address)) {
				$costumerArgs['address'] = ['text' => ''];
			}
			//2
			$telegram = $this->customerTelegram($order);
			if ($telegram) {
				$costumerArgs['customFields']['telegram'] = $telegram;
			}
			//3
			if (!isset($order->customer->customFields->otkuda_uznal_o_nas) && isset($order->customFields->otkuda_o_nas_uznal)) {
				$costumerArgs['customFields']['otkuda_uznal_o_nas'] = $order->customFields->otkuda_o_nas_uznal;
			}
			//4
			if (isset($order->customFields->ya_client_id_order)) {
				$costumerArgs['customFields']['ya_client_id'] = $order->customFields->ya_client_id_order;
			}

			if (empty($costumerArgs)) continue;
			if (count($costumerArgs['customFields'])) $loggerCustomersData[$i] = $costumerArgs['customFields'];

			$args = [
				'by' => 'id',
				'site' => $order->site,
				'customer' => json_encode($costumerArgs)
			];
			$response = new Response_customers_edit_post($order->customer->id);
			$response->editCustomerInCrm($args);
			if ($response->hasError()) throw new \Exception($response->getError());

			$i++;
		}
		$this->logger->addToLog('customersDataToApply', $loggerCustomersData);
	}

	private function customerTelegram($order)
	{
		if (!isset($order->customFields->messenger_zakazchika)) return '';
		return OrderData_telegram::get($order->customFields->messenger_zakazchika);
	}
}
