<?php

require_once dirname(__DIR__) . '/!autoload.php';

use php2steblya\Config;
use php2steblya\Script;
use php2steblya\ErrorHandler;
use print_card\CardFactory;

Config::init();
ErrorHandler::init();

class PrintCard extends Script
{
	public function init()
	{
		try {
			$this->logger->addRoot('source', 'print_card')->addRoot('script_data', $this->scriptData);
			$cardData = $this->getCardData();
			$cardFactory = new CardFactory();
			$card = $cardFactory->getInstance($cardData);
			echo $card->render();
		} catch (\Throwable $e) {
			$this->logger->addError($e);
			$logData = json_encode($this->logger->getLogData(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
			echo "<script>console.log($logData);</script>";
		}
	}

	// получает данные карточки в зависимости от запроса.
	private function getCardData(): array
	{
		$cardData = [];

		if (!empty($_GET['shop_crm_id']) && !empty($_GET['sku'] && !empty($_GET['order_id']))) {
			$this->logger->add('request', 'order_id & shop_crm_id & sku');

			//товар в дб
			$this->logger->setGroup('получаем продукт из db');
			$where = [
				'sku' => str_pad($_GET['sku'], 3, '0', STR_PAD_LEFT),
				'shop_crm_id' => $_GET['shop_crm_id']
			];
			$productDb = $this->getProductFromDb($where);
			$this->logger->add('product_db', $productDb);

			//заказ в crm
			$this->logger->setGroup('получаем заказ из crm');
			$order = $this->getOrderFromCrm($_GET['order_id']);
			$this->logger->add('order', $order);

			//товар в crm
			$this->logger->setGroup('находим продукт с карточкой');
			$this->logger->add('products', $order->items);
			$productRC = $this->getProductFromOrder($order->items);
			$this->logger->add('product_rc', $productRC);

			//shop_crm_code
			$this->logger->setGroup('получаем shop_crm_code');
			$shopCrmCode = $this->getShopCrmCode($_GET['shop_crm_id']);
			$this->logger->add('shop_crm_code', $shopCrmCode);

			$cardData = [
				'title' => $productRC->offer->name,
				'type' => $productDb['card_type'],
				'our_content' => $productDb['card_content'],
				'customer_content' => $order->customFields->text_v_kartochku ?? '',
				'customer_choice' => $productRC->properties->{'viebri-kartochku'}->value ?? 'с нашей карточкой',
				'shop_crm_code' => $shopCrmCode
			];
		} else {
			// Если параметров нет, показываем пустой шаблон
			$this->logger->add('request', 'default');

			$shopCrmCode = $_GET['site'] ?? '2steblya';

			$cardData = [
				'title' => 'LOREM IPSUM',
				'type' => 'text',
				'our_content' => 'lorem ipsum sit amet',
				'customer_content' => '',
				'customer_choice' => 'с нашей карточкой',
				'shop_crm_code' => $shopCrmCode
			];
		}

		$this->logger->addRoot('card_data', $cardData);
		return $cardData;
	}

	// получает данные заказа из CRM.
	private function getOrderFromCrm(int $orderId): object
	{
		$args = ['filter' => ['ids' => [$orderId]]];
		$apiResponse = $this->retailcrm->orders()->get($args);
		return $apiResponse->orders[0] ?? throw new \Exception("заказ не найден в CRM");
	}

	// получает товар из заказа CRM.
	private function getProductFromOrder(array $items): object
	{
		if (empty($items)) {
			throw new \Exception("в заказе нет товаров");
		}

		foreach ($items as $item) {
			if (isset($item->properties->{'viebri-kartochku'}->value)) {
				return $item;
			}
		}

		throw new \Exception("в заказе нет товаров с карточкой");
	}

	// получает товар из БД.
	private function getProductFromDb(array $where): array
	{
		$args = ['where' => $where, 'limit' => 1];
		return $this->db->products()->get($args) ?? throw new \Exception("товар не найден в базе");
	}

	// получает код CRM-магазина по его ID.
	private function getShopCrmCode(int $shopCrmId): string
	{
		$args = [
			'fields' => ['shop_crm_code'],
			'where' => ['shop_crm_id' => $shopCrmId],
			'limit' => 1
		];
		return $this->db->shops()->get($args) ?? throw new \Exception("магазин не найден");
	}
}

$printCard = new PrintCard($_GET);
$printCard->init();
