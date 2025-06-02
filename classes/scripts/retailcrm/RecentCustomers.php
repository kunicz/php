<?php

namespace php2steblya\scripts\retailcrm;

use php2steblya\order\OrderData_phone;
use php2steblya\order\OrderData_telegram;
use php2steblya\Script;

class RecentCustomers extends Script
{
	private array $orders; // Массив заказов
	private array $customers; // Массив клиентов из закзов
	private array $duplicates; // Массивы дубликатов клиентов

	public function init()
	{
		$this->collectRecentOrders();
		$this->sortRecentOrders();
		$this->collectRecentCustomers();
		$this->defineCustomersDuplicates();
		$this->combineCustomersInCrm();
	}

	// Получает заказы, созданные в последние 3 дня.
	private function collectRecentOrders(): void
	{
		$this->logger->setGroup("1. Получаем заказы");
		$args = [
			'filter' => [
				'createdAtFrom' => date('Y-m-d', strtotime('-3 day')),
				'createdAtTo' => date('Y-m-d', strtotime('-1 day'))
			]
		];
		$response = $this->retailcrm->orders()->get($args);
		$this->orders = $response->orders;
		$this->logger->add('orders_initial', $this->orders);
	}

	// Сортирует заказы по дате создания в порядке убывания (от позднего к раннему).
	private function sortRecentOrders(): void
	{
		usort($this->orders, function ($a, $b) {
			return strtotime($b->createdAt) <=> strtotime($a->createdAt);
		});
		$this->logger->add('orders_sorted', $this->orders);
	}

	// Возвращает массив уникальных клиентов из заказов.
	// Подготваливает их к слиянию (собирает актуальную информацию и подставляет в соответствующие поля).
	private function collectRecentCustomers(): void
	{
		$this->logger->setGroup("2. Получаем клиентов");
		foreach ($this->orders as $order) {
			$phone = $order->customer->phones[0]->number ?? null;
			if (!$phone) continue;

			$phone = OrderData_phone::tenDigits($phone);
			if (empty($this->customers[$phone])) {
				$this->customers[$phone] = $order->customer;
			} else {
				$this->updateCustomer($order, $phone);
			}
		}
		$this->logger->add('customers', $this->customers);
	}

	// Обновляем данные клиента
	private function updateCustomer(object $order, string $phone): void
	{
		// базовый слепок клиента с id (customer->id)
		$a = &$this->customers[$phone];
		// альтернативный вариант его же, но из другого заказа (он более ранний хронологически)
		$b = $order->customer;

		$this->otkudaUznal($a, $b, $order);
		$this->yaClientId($a, $b, $order);
		$this->telegram($a, $b, $order);
		$this->email($a, $b);
		$this->address($a);
	}

	// Получаем из срм дубликатоы всех клиентов
	private function defineCustomersDuplicates(): void
	{
		$this->duplicates = [];
		$this->logger->setGroup("3. Находим дубликаты");
		foreach ($this->customers as $phone => &$customer) {
			try {
				$this->logger->setSubGroup($phone);

				$response = $this->retailcrm->customers()->get(['filter' => ['name' => $phone]]);
				// если найдено меньше 2 клиентов, то дубликатов нет
				// 0 - вообще не найден клиент (невозможно)
				// 1 - наш исходный $customer
				if (count($response->customers) < 2) continue;

				$this->duplicates[$phone] = [];
				foreach ($response->customers as $dup) {
					if ($dup->id === $customer->id) continue;
					$this->logger->add($dup->id, $dup);
					$this->duplicates[$phone][] = $dup;
				}

				// "откуда узнал" действует по обратной логике
				// он наоборот не должен перезаписывать значение, если оно уже есть,
				// самое раннее значение будет самым актуальным
				// памятка по названиями ключей:
				// otkuda_uznal_o_nas - ключ для customer
				// otkuda_o_nas_uznal - ключ для order
				$hasOtkudaUznal = !!array_filter(
					$this->duplicates[$phone],
					fn($dup) => !empty($dup->customFields->otkuda_o_nas_uznal)
				);
				if ($hasOtkudaUznal) $customer->customFields['otkuda_uznal_o_nas'] = '';
			} catch (\Exception $e) {
				$this->logger->addError($e);
				//self::notifyAdmin($e);
			} finally {
				$this->logger->exitSubGroup();
			}
		}
		$this->logger->add('duplicates', $this->duplicates);
	}

	// Схлопываем (сливаем) дубликаты и клиента.
	// Основным клиентом становится самый актуальный по дате создания.
	// Но он впитывает в себя всю недостающую информацию из дубликатов в срм
	private function combineCustomersInCrm(): void
	{
		$this->logger->setGroup("4. Сливаем клиентов");
		foreach ($this->duplicates as $id => $duplicates) {
			if (empty($duplicates)) continue;

			$this->logger->setSubGroup($id);
			$args = [
				'resultCustomer' => $this->customers[$id],
				'customers' => $this->duplicates[$id]
			];
			$this->retailcrm->customers()->combine($args);
		}
	}

	// $a - более новый клиент
	// $b - более старый дубликат
	// памятка по названиями ключей:
	// otkuda_uznal_o_nas - ключ для customer
	// otkuda_o_nas_uznal - ключ для order
	private function otkudaUznal(object &$a, object $b, object $order): void
	{
		if (empty($b->customFields['otkuda_uznal_o_nas'])) return;
		$b->customFields['otkuda_uznal_o_nas'] = $order->otkuda_o_nas_uznal ?? '';
		$this->setCustomField($a, $b, 'otkuda_uznal_o_nas');
	}

	private function yaClientId(object &$a, object $b, object $order): void
	{
		if (!empty($a->customFields['ya_client_id'])) return;
		$b->customFields['ya_client_id'] = $order->ya_client_id ?? '';
		$this->setCustomField($a, $b, 'ya_client_id');
	}

	private function telegram(object &$a, object $b, object $order): void
	{
		if (!empty($a->customFields['telegram'])) return;
		$b->customFields['telegram'] = OrderData_telegram::get($order->messenger_zakazchika ?? '');
		$this->setCustomField($a, $b, 'telegram');
	}

	private function email(object &$a, object $b): void
	{
		if ($b->email) $a->email = $b->email;
	}

	private function address(object &$a): void
	{
		$a->address = ['text' => ''];
	}

	private function setCustomField(object $a, object $b, string $key): object
	{
		if (!empty($b->customFields[$key])) $a->customFields[$key] = $b->customFields[$key];
		return $a;
	}
}


/*

// легаси - но пока надо подержать
// предыдущая версия RecentCustomers работала в поштучном режиме — каждый заказ → клиент → обработка → слияние.
// Это приводило к повторной обработке одного и того же клиента,
// включая падения при попытке схлопнуть уже удалённого клиента.

class RecentCustomers extends Script
{
	private object $order; // Объект заказа
	private object $customer; // Объект клиента
	private array $duplicates; // Массив дубликатов клиентов

	public function init()
	{
		$orders = $this->collectRecentOrders();
		foreach ($orders as $order) {
			$this->order = $order;
			$this->customer = $order->customer;
			$this->duplicates = $this->collectCustomerDuplicates();
			$this->updateCustomerData();
			$this->collapseCustomerDuplicates();
		}
	}

	// Получает заказы, созданные в последние 3 дня.
	private function collectRecentOrders()
	{
		$this->logger->setGroup("Получаем заказы");
		$args = [
			'filter' => [
				'createdAtFrom' => date('Y-m-d', strtotime('-3 day')),
				'createdAtTo' => date('Y-m-d', strtotime('-1 day'))
			]
		];
		$apiResponse = $this->retailcrm->orders()->get($args);
		return $apiResponse->orders;
	}

	// Собирает список клиентов-дубликатов для текущего клиента.
	private function collectCustomerDuplicates(): array
	{
		Logger::getInstance()->setGroup("клиент {$this->customer->id}. собираем дубликаты");

		$duplicates = [];

		try {
			$phoneNumber = $this->customer->phones[0]->number ?? null;

			if (empty($phoneNumber)) {
				throw new \Exception('у клиента не указан номер телефона');
			}

			$args = ['filter' => [
				'name' => $phoneNumber
			]];
			$apiResponse = $this->retailcrm->customers()->get($args);
			$customers = $apiResponse->customers ?? [];

			if (!empty($customers)) {
				$customers = $this->sortCustomersByCreatedAt($customers);
				$duplicates = $this->removeCustomerFromDuplicates($customers);
			}
		} catch (\Exception $e) {
			//дальше не передаем, так как отсутсвие дубликатов у клиента не является терминальной ошибкой
		}

		$this->logger->add('duplicates', $duplicates);

		return $duplicates;
	}

	// Сортирует массив клиентов по дате создания (createdAt) в порядке возрастания.
	private function sortCustomersByCreatedAt(array $customers): array
	{
		usort($customers, function ($a, $b) {
			return strtotime($a->createdAt) <=> strtotime($b->createdAt);
		});

		return $customers;
	}

	// Удаляет исходного клиента из списка дубликатов.
	private function removeCustomerFromDuplicates(array $customers): array
	{
		return array_filter($customers, function ($duplicateCustomer) {
			return $duplicateCustomer->id !== $this->customer->id;
		});
	}

	// Обновляет данные клиента в RetailCRM.
	private function updateCustomerData(): void
	{
		$this->logger->setGroup("клиент {$this->customer->id}. обновляем данные");

		$data = $this->collectCustomerData();
		if (empty($data)) return;

		try {
			$args = [
				'id' => $this->customer->id,
				'args' => [
					'site' => $this->order->site,
					'customer' => $data
				]
			];
			$apiResponse = $this->retailcrm->customers()->edit($args);
			$this->logger->add('response', $apiResponse);
		} catch (\Exception $e) {
			//сообщаем админу, если не удалось обновить данные клиента, но скрипт не прерываем
			self::notifyAdmin($e);
		}
	}

	// Собирает данные для обновления клиента.
	private function collectCustomerData(): array
	{
		$data = [];
		$data = $this->getCustomerTelegram($data);
		$data = $this->getCustomerEmail($data);
		$data = $this->getaddress($data);
		$data = $this->getCustomerYaClientId($data);
		$data = $this->getCustomerOtkudaUznal($data);

		$this->logger->add('data', $data);

		return $data;
	}

	// Сливает дубликаты клиента с основным клиентом.
	private function collapseCustomerDuplicates(): void
	{
		if (empty($this->duplicates)) return;
		$this->logger->setGroup("клиент {$this->customer->id}. схлопываем дубликаты");
		try {
			$args = [
				'resultCustomer' => $this->customer,
				'customers' => $this->duplicates
			];
			$this->retailcrm->customers()->combine($args);
		} catch (\Exception $e) {
			//сообщаем админу, если не удалось слить дубликаты клиента, но скрипт не прерываем
			self::notifyAdmin($e);
		}
	}

	// Получает данные Telegram для клиента.
	private function getCustomerTelegram(array $data): array
	{
		if (isset($this->order->customFields->messenger_zakazchika)) {
			$telegram = OrderData_telegram::get($this->order->customFields->messenger_zakazchika);
			$data['customFields']['telegram'] = $telegram;
		}
		return $data;
	}

	// Получает email для клиента.
	private function getCustomerEmail(array $data): array
	{
		if (isset($this->customer->email)) {
			$data['email'] = $this->customer->email;
		}
		return $data;
	}

	// Получает адрес клиента.
	private function getaddress(array $data): array
	{
		if (isset($this->customer->address)) {
			$data['address'] = ['text' => ''];
		}
		return $data;
	}

	// Получает идентификатор клиента из Яндекс.
	private function getCustomerYaClientId(array $data): array
	{
		if (isset($this->order->customFields->ya_client_id_order)) {
			$data['customFields']['ya_client_id'] = $this->order->customFields->ya_client_id_order;
		}
		return $data;
	}

	// Получает информацию о том, откуда клиент узнал о компании.
	private function getCustomerOtkudaUznal(array $data): array
	{
		$otkudaUznal = null;
		//перебираем дубликаты клиента от раннего к позднему
		foreach ($this->duplicates as $duplicate) {
			if (!isset($duplicate->customFields->otkuda_o_nas_uznal)) continue;
			$otkudaUznal = $duplicate->customFields->otkuda_o_nas_uznal;
			break;
		}
		if ($otkudaUznal && $this->customer->customFields->otkuda_o_nas_uznal != $otkudaUznal) {
			$data['customFields']['otkuda_uznal_o_nas'] = $otkudaUznal;
		}
		return $data;
	}
}

*/
