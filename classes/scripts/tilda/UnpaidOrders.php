<?php

namespace php2steblya\scripts\tilda;

use php2steblya\db\DbTable;
use php2steblya\order\OrderData_telegram;
use php2steblya\Script;
use php2steblya\order\handlers\Retailcrm as OrderRetailcrm;
use php2steblya\helpers\DateTime;

// скрипт для обработки неоплаченных заказов из Tilda.
class UnpaidOrders extends Script
{
	private array $od; // массив данных заказа (od) полученный из бд (ориниально из Тильды)
	private DbTable $db_orders; // абстракция над классом таблицы в бд
	private array $unpaidOrder; // массив массивов данных неоплаченных заказов (od)
	private array $shop; // массив данных магазина (shop) полученный из бд
	private const MINUTES_TO_SKIP = 10;

	public function init(): void
	{
		foreach ($this->shops as $shop) {
			$this->shop = $shop;
			$this->db_orders = $this->db->orders();

			try {
				$this->processShop();
			} catch (\Exception $e) {
				$this->logger->addError($e);
			}
		}
	}

	// итерирует по всем записям из бд о неоплаченных заказах для конкретного магазина.
	// запускает обработку каждого заказа.
	private function processShop(): void
	{
		$this->logger->setGroup($this->shop['shop_crm_code']);

		$unpaidOrders = $this->getUnpaidOrders();
		if (empty($unpaidOrders)) return;

		foreach ($unpaidOrders as $unpaidOrder) {
			$this->unpaidOrder = $unpaidOrder;
			try {
				$this->processOrder();
			} catch (\Exception $e) {
				$this->logger->addError($e);
			}
		}
	}

	// получает список неоплаченных заказов для указанного магазина.
	private function getUnpaidOrders(): array
	{
		$args = [
			'where' => [
				'shop_crm_id' => $this->shop['shop_crm_id'],
				'paid' => 0
			]
		];
		$response = $this->db_orders->get($args);
		$this->logger->add('unpaid_orders', $response);
		return $response;
	}

	// обрабатывает неоплаченный заказ.
	private function processOrder(): void
	{
		$orderId = $this->unpaidOrder['tilda_order_id'];
		$this->logger->setGroup("{$this->shop['shop_crm_code']}. заказ $orderId");

		if ($this->shouldSkipOrder()) return;

		$this->od = json_decode($this->unpaidOrder['order_data'], true);
		if (!is_array($this->od)) throw new \Exception("Не удалось декодировать заказ $orderId");
		$this->logger->add('order_data', $this->od);

		if ($this->paidOrderExists()) {
			$this->deleteUnpaidOrder();
			return;
		}

		$telegramChannelId = $this->getTelegramChannelId();
		if (!$telegramChannelId) return;

		$this->sendMessageToTelegramChannel($telegramChannelId);
		$this->sendToCrm();
		$this->deleteUnpaidOrder();
	}

	// проверяет, стоит ли пропустить обработку заказа.
	private function shouldSkipOrder(): bool
	{
		if (isset($this->scriptData['skip'])) return false;
		$minutesPassed = DateTime::minutesFromNowTo($this->unpaidOrder['createdOn']);
		$shouldSkip = $minutesPassed <= self::MINUTES_TO_SKIP;
		$this->logger
			->add('minutes_passed', $minutesPassed)
			->add('should_skip', $shouldSkip);
		return $shouldSkip;
	}

	// обрабатывает существование оплаченного заказа.
	private function paidOrderExists(): bool
	{
		$args = [
			'where' => [
				'shop_crm_id' => $this->shop['shop_crm_id'],
				'tilda_order_id' => $this->unpaidOrder['tilda_order_id'],
				'paid' => 1
			]
		];
		$response = $this->db_orders->exist($args);
		$this->logger->add('paid_order_exist', $response);
		return $response;
	}

	// получает ID Telegram-канала для уведомлений.
	private function getTelegramChannelId(): string
	{
		$city_id = $this->od['shop']['city_id'];
		$shop_crm_id = $this->od['shop']['shop_crm_id'];
		$args = [
			'fields' => [
				'telegram_id'
			],
			'where' => [
				'city_id' => $city_id,
				'name' => 'orders_unpaid',
				'shop_crm_id' => $shop_crm_id
			],
			'limit' => 1
		];
		$telegramChannelId = $this->db->telegram_channels()->get($args);
		$this->logger->add('telegram_channel_id', $telegramChannelId);
		if (empty($telegramChannelId)) {
			throw new \Exception("телеграм канал для city_id $city_id и shop_crm_id $shop_crm_id не найден в бд");
		}
		return $telegramChannelId;
	}

	// отправляет сообщение в указанный Telegram-канал.
	private function sendMessageToTelegramChannel(string $telegramChannelId): void
	{
		$args = [
			'chat_id' => $telegramChannelId,
			'parse_mode' => 'MarkdownV2',
			'text' => OrderData_telegram::getMessageForChannel($this->od)
		];
		$this->telegram->setBotName('orders')->messages()->send($args);
	}

	// отправляет заказ в CRM.
	private function sendToCrm(): void
	{
		$retailcrm = new OrderRetailcrm($this->od, $this->script);
		$retailcrm->execute();
	}

	// удаляет неоплаченный заказ из базы данных
	private function deleteUnpaidOrder(): void
	{
		$this->logger->setGroup("{$this->shop['shop_crm_code']}. удаление заказа {$this->unpaidOrder['tilda_order_id']}");
		$args = [
			'where' => [
				'db_id' => $this->unpaidOrder['db_id']
			]
		];
		$deleted = $this->db_orders->delete($args);
		$this->logger->add('удалено строк', $deleted);
	}
}
