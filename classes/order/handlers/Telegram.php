<?php

namespace php2steblya\order\handlers;

use php2steblya\order\handlers\Abstract_handler as OrderHandler;
use php2steblya\order\OrderData_telegram;

class Telegram extends OrderHandler
{
	private string $channelName;
	private string $channelId;

	public function execute()
	{
		if (empty($this->od['payment']['recieved'])) return;

		$this->validateData();
		$this->getChannel();
		$this->validateChannel();
		$this->sendMessage();
	}

	private function validateData()
	{
		if (empty($this->od['shop']['shop_crm_id'])) throw new \Exception('в od отсутствует поле shop_crm_id');
		if (empty($this->od['shop']['city_id'])) throw new \Exception('в od отсутствует поле city_id');
	}

	// устанавливаем необходимый телеграмм канал
	private function getChannel()
	{
		$this->logger->setSubGroup('channel');
		$this->channelName = 'orders_paid';
		$args = [
			'fields' => ['telegram_id'],
			'where' => [
				'name' => $this->channelName,
				'city_id' => $this->od['shop']['city_id'],
				'shop_crm_id' => $this->od['shop']['shop_crm_id']
			],
			'limit' => 1
		];
		$this->channelId = $this->script->db->telegram_channels()->get($args);
		$this->logger->exitSubGroup();
	}

	// проверяем, что канал найден
	private function validateChannel()
	{
		$shop = $this->od['shop']['shop_crm_code'];
		$city = $this->od['shop']['city_title'];
		if (!($this->channelId)) throw new \Exception("не найден канал $this->channelName для магазина $shop ($city)");
	}

	// отправляем сообщение в телеграмм канал
	private function sendMessage()
	{
		$this->logger->setSubGroup('message');
		$args = [
			//'chat_id' => $_ENV['TELEGRAM_ADMIN_CHAT_ID'],
			'chat_id' => $this->channelId,
			'parse_mode' => 'MarkdownV2',
			'text' => OrderData_telegram::getMessageForChannel($this->od)
		];
		$this->script->telegram->setBotName('orders')->messages()->send($args);
		$this->logger->exitSubGroup();
	}
}
