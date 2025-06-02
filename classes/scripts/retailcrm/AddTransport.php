<?php

namespace php2steblya\scripts\retailcrm;

use php2steblya\Script;

class AddTransport extends Script
{
	private $orderId;
	private $order;

	public function init(): void
	{
		if (empty($this->scriptData['id'])) throw new \Exception("не передан id заказа");
		$this->orderId = $this->scriptData['id'];
		$this->getOrderFromCrm();
		$this->addTransportItemToOrder();
	}

	// получает заказ из RetailCRM по ID.
	private function getOrderFromCrm(): void
	{
		$args = ['filter' => ['ids' => [$this->orderId]]];
		$apiResponse = $this->retailcrm->orders()->get($args);
		$this->order = $apiResponse->orders[0];
	}

	// добавляет транспортировочное в заказ.
	private function addTransportItemToOrder(): void
	{
		// чтобы в заказ добавлялось транспортировчное, пришлось создать товар "транспортировочное" в каждом сайте
		// потому что срм через апи не умеет добавлять в заказ товары из других магазинов (в нашем случае, остатки)
		// добавлять просто товар без привязки к магазину также нельзя, так как будет возникать ошибка бронирования
		// для того, чтобы для разных городов (менеджеров) и разных сайтов выводилось свое транспортировочное со своей ценой, создал массив $transport в котором
		// нумерной ключ в ценах - это id менеджера
		// ids транспортировочных - это прямо магическое число. В самой срм его нигде не найти. Чтоб его получить, нужно отключить user_jscss в срм,
		// добавить в какой-то заказ транспортировочное нужного магазина, сохранить, а потом запросить этот заказ через апи (https://2steblya.ru/phptest и скрипт: RetailCrm_orderData)
		// в logger можно будет найти order -> 0 -> items -> offer -> id
		$managers = [
			'мск' => 13
		];
		$transport = [];
		$transport[$managers['мск']] =
			[
				'price' => [
					'2steblya' => 600,
					'2steblya_white' => 600,
					'staytrueflowers' => 600,
					'gvozdisco' => 700
				],
				'ids' => [
					'2steblya' => 3970,
					'2steblya_white' => 5220,
					'staytrueflowers' => 3971,
					'gvozdisco' => 4104
				]
			];
		$site = $this->order->site;
		$items = $this->order->items;
		$manager = $this->order->managerId;
		// если данные для менеджера не найдены, то менеджер по умолчанию - москва
		// чтобы избежать noname товаров и бесконечной перезагрузки в retailcrm 
		if (!in_array($manager, array_keys($transport))) $manager = $managers['мск'];

		$items[] = [
			'initialPrice' => $transport[$manager]['price'][$site],
			'purchasePrice' => $transport[$manager]['price'][$site],
			'quantity' => 1,
			'offer' => ['id' => $transport[$manager]['ids'][$site]]
		];
		$args = [
			'id' => $this->orderId,
			'args' => [
				'by' => 'id',
				'site' => $site,
				'order' => json_encode(['items' => $items])
			]
		];
		$this->retailcrm->orders()->edit($args);
	}
}
