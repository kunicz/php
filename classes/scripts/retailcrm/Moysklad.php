<?php

namespace php2steblya\scripts\retailcrm;

use php2steblya\Script;

class Moysklad extends Script
{
	public function init()
	{
		return;
		$argsToGetOrderCrm = $this->getArgsToGetOrderCrm();
		$orderCrm = $this->getOrderCrm($argsToGetOrderCrm);
		// по-тихому без Script::fail сворачиваем скрипт тут, если заказ не найден
		// когда скрипт иницируется тригером при удалении в срм, он точно не сможет быть найден
		// надо подумать, как отличать запуски скрипта при изменении и при удалении заказа
		if (!$orderCrm) return;

		$orderCrmItems = $this->getOrderCrmItems($orderCrm);
		isset($this->scriptData['update']) ? $this->updateOrderMs($orderCrm) : $this->insertOrdersToMs($orderCrmItems, $orderCrm);
	}

	// Определяет ID заказа в RetailCRM.
	// Приоритетным источником является `$_GET['id'] (scriptData)`,
	// что позволяет выполнять ручное тестирование через `phptest (RetailCrm_MoySklad&id=xxxx)`.
	// В обычном режиме ID передается в `$_POST триггером МойСклад в RetailCRM.
	private function getArgsToGetOrderCrm(): array
	{
		$this->logger->setGroup("id и site заказа из retailcrm");

		$data = [];
		if (isset($this->scriptData['id'])) {
			$data['id'] = $this->scriptData['id'];
			$data['site'] = $this->scriptData['site'] ?? null;
		} else {
			$data['id'] = $this->scriptData['id'] ?? null;
			$data['site'] = $this->scriptData['site'] ?? null;
		}
		$this->logger->add('data', $data);

		if (!isset($data['id']) || !$data['id']) throw new \Exception("не передан id заказа из retailcrm");
		if (!isset($data['site']) || !$data['site']) throw new \Exception("не передан site заказа из retailcrm");

		return $data;
	}

	// Получает заказ из RetailCRM по ID.
	private function getOrderCrm(array $args): ?object
	{
		$this->logger->setGroup("получаем заказ из retailcrm");

		// тут проблема:
		// скрипт запускается триггером в том числе и когда заказ удаляется
		// соответственно он не может быть найден, поэтому выброс исключения и краш скрипта с ошибкой - плохая идея
		// пока не придумал решения изящнее, просто отлавливаю исключение и логгирую
		try {
			$response = $this->retailcrm->orders()->getById($args);
			$orderCrm = $response->order;
			$this->logger->add('order', $orderCrm);
			return $orderCrm;
		} catch (\Exception $e) {
			//$this->logger->addError($e);
			return null;
		}
	}

	// Получает товары для МоегоСклада.
	// Отбираются только те товары, которые являются каталожными сборными.
	private function getOrderCrmItems(object $orderCrm): array
	{
		$this->logger->setGroup("получаем товары из заказа {$orderCrm->id}");

		$items = [];
		foreach ($orderCrm->items as $orderCrmItem) {
			if (!$this->isItemForMs($orderCrmItem)) continue;
			$items[] = $orderCrmItem;
		}

		$this->logger->add('items', $items);

		return $items;
	}

	// Проверяет, является ли товар сборным каталоговым, т.е. таким, который должен попадать в МойСклад.
	// Проверка заключается в наличии свойства moyskladid.value
	// Принял решение создавать кастомное свойство moyskladid,
	// так как оно сохраняется при слиянии и разделении заказов в retailcrm
	private function isItemForMs(object $orderCrmItem): bool
	{
		if (isset($orderCrmItem->properties->artikul) && ($orderCrmItem->properties->artikul->value ?? null) == ARTIKUL_DONAT) return false;
		return !!$this->getMsId($orderCrmItem);
	}

	// Получает кастомное свойство moyskladid.value в товаре.
	private function getMsId(object $orderCrmItem): ?string
	{
		return $orderCrmItem->properties->moyskladid->value ?? null;
	}

	// Добавляет заказы в МойСклад.
	private function insertOrdersToMs(array $orderCrmItems, object $orderCrm): void
	{
		if (empty($orderCrmItems)) return;
		foreach ($orderCrmItems as $orderCrmItem) {
			$msId = $this->getMsId($orderCrmItem);
			if (!$msId) continue;

			$msOrder = $this->getOrderMs($msId);
			if (!empty($msOrder)) continue;

			$this->createOrderMs($orderCrmItem, $orderCrm);
		}
	}

	// Получает заказ в МойСклад по msId товара в RetailCRM.
	private function getOrderMs(string $moyskladid): ?object
	{
		$this->logger->setGroup("получаем заказ {$moyskladid} в МойСклад");

		$args = ['filter' => ['externalCode' => $moyskladid]];
		$apiResponse = $this->moysklad->orders()->get($args);
		$orders = $apiResponse->rows;
		$exist = count($orders) > 0;
		$this->logger->add('exist', $exist);

		if (!$exist) return null;

		$this->logger->add('order', $orders[0]);
		return $orders[0];
	}

	// Создает заказ в МойСклад.
	// 
	// name - название заказа = названия товара
	// project - сайт, на котором был совершен заказ
	// externalCode - moyskladid - кастомное свойство, которое должно быть создано в retailcrm для каждого товара
	// owner - владелец заказа (флорист или босс)
	// organization - организация
	// agent - контрагент/клиент/покупатель
	//
	// для создания заказа в МоемСкладе необходимо обязательно передавать некоторые поля, поэтому
	// в качестве их аргументов передаются заглушки, просто для того, чтобы инициировать получение их метаданных.
	// 
	// organization: ИП Авдеева
	// 
	// agent: принял решение писать все заказы на одного дефолтного контрагента.
	// чтобы у нас был единственный источник правды по контрагентам (клиентам) - retailcrm
	// так как контрагент покупки может меняться в срм, но это ничего не меняет в логике работы с заказами в МоемСкладе
	// например: Наличие (витрина) -> Покупатель (заказ)
	private function createOrderMs(object $orderCrmItem, object $orderCrm): void
	{
		$idMs = $this->getMsId($orderCrmItem);
		$this->logger->setGroup("создаем заказ {$idMs} в МойСклад");

		$data = [
			'externalCode' => $idMs,
			//'created' => $orderCrm->createdAt, // создается автоматически и не редактируется
			'project' => $orderCrm->site,
			'orderCrmId' => $orderCrm->id,
			'name' => "{$orderCrmItem->offer->displayName} :: {$idMs}", //надо добавлять id, потому что name должно быть уникальным
			'owner' => $orderCrm->customFields->florist,
			'organization' => 'default',
			'agent' => 'default'
		];
		$this->moysklad->orders()->create($data);
	}

	// Обновляет заказ в МойСклад.
	private function updateOrderMs(object $orderCrm): void
	{
		$this->logger->setGroup("обновляем заказ {$orderCrm->id} в МойСклад");

		$orderMs = $this->getOrderMs((int) $orderCrm->id);
		if (!$orderMs) return;

		$data = [
			'id' => $orderMs->id,
			'args' => []
		];
		switch ($this->scriptData['update']) {
			case 'owner':
				$data['args']['owner'] = $orderCrm->customFields->florist;
				break;
		}
		$this->moysklad->orders()->edit($data);
	}
}
