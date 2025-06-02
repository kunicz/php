<?php

namespace php2steblya\scripts\retailcrm;

use php2steblya\order\OrderData_phone as Phone;
use php2steblya\Script;

class CustomersDuplicates extends Script
{
	// инициализирует процесс обработки дубликатов клиентов
	public function init()
	{
		$customers = $this->getAllCustomers();
		$duplicates = $this->filterCustomers($customers);
		$this->iterateDuplicatesGroups($duplicates);
	}

	// получает всех клиентов из RetailCRM
	private function getAllCustomers()
	{
		$this->logger->setGroup('получаем всех клиентов');
		$apiResponse = $this->retailcrm->customers()->getAll();
		$this->logger->add('customers', $apiResponse);
		return $apiResponse->customers;
	}

	// фильтрует клиентов, чтобы найти дубликаты на основе телефонных номеров
	private function filterCustomers(array $customers): array
	{
		$this->logger->setGroup('фильтруем дубликаты клиентов');

		$phoneMap = [];
		foreach ($customers as $customer) {
			// пропускаем клиентов без телефона или сайта
			if (empty($customer->phones) || !isset($customer->phones[0]->number) || empty($customer->site)) continue;

			// преобразуем телефон в формат с 10 цифрами
			$phoneTenDigits = Phone::tenDigits($customer->phones[0]->number);

			// используем массив с ключом, который включает и телефон, и сайт
			$key = $phoneTenDigits . '-' . $customer->site;

			// группируем клиентов по этому ключу
			$phoneMap[$key][] = $customer;
		}

		// оставляем только те группы, где количество клиентов больше 1 (дубликаты)
		$duplicates = array_filter($phoneMap, static fn(array $customers) => count($customers) > 1);
		$this->logger->add('duplicates', $duplicates);
		$this->logger->add('count', count($duplicates));

		return $duplicates;
	}


	// обрабатывает группы клиентов-дубликатов.
	private function iterateDuplicatesGroups(array $duplicates)
	{
		foreach ($duplicates as $phone => $customers) {
			$customers = $this->sortCustomersByCreatedAt($customers);
			$data = $this->collectDataForUpdate($customers);
			$resultCustomer = array_pop($customers);
			$this->updateResultCustomer($phone, $resultCustomer, $data);
			$this->collapseCustomerDuplicates($phone, $resultCustomer, $customers);
		}
	}

	// сортирует массив клиентов по дате создания в порядке возрастания.
	private function sortCustomersByCreatedAt(array $customers): array
	{
		usort($customers, function ($a, $b) {
			return strtotime($a->createdAt) <=> strtotime($b->createdAt);
		});
		return $customers;
	}

	// собирает данные для обновления клиента на основе данных всех дубликатов.
	private function collectDataForUpdate(array $customers): array
	{
		$data = [];
		$data['createdAt'] = $customers[0]->createdAt;
		$data['address']['text'] = '';
		$data = $this->addOtkudaUznalToData($data, $customers);
		return $data;
	}

	// добавляет значение customField "otkuda_uznal_o_nas" в данные для обновления клиента.
	private function addOtkudaUznalToData(array $data, array $customers): array
	{
		$otkudaUznal = null;
		foreach ($customers as $customer) {
			$otkudaUznal = $customer->customFields->otkuda_uznal_o_nas ?? $otkudaUznal;
			if ($otkudaUznal) break;
		}
		if ($otkudaUznal) {
			$data['customFields']['otkuda_uznal_o_nas'] = $otkudaUznal;
		}
		return $data;
	}

	// обновляет основного клиента на основе собранных данных.
	private function updateResultCustomer(string $phone, object $resultCustomer, array $data): void
	{
		if (empty($data)) return;

		$this->logger->setGroup("обновляем клиента $phone");

		$args = [
			'id' => $resultCustomer->id,
			'args' => [
				'site' => $resultCustomer->site,
				'customer' => $data
			]
		];
		$this->logger->add('args', $args);

		try {
			$this->retailcrm->customers()->edit($args);
		} catch (\Exception $e) {
			self::notifyAdmin($e);
		}
	}

	// сливает дубликаты клиента в одного основного клиента.
	private function collapseCustomerDuplicates(string $phone, object $resultCustomer, array $customers): void
	{
		$this->logger->setGroup("схлопываем дубликаты клиента $phone");
		$this->logger->add('resultCustomer', $resultCustomer);
		$this->logger->add('dublicates', $customers);

		try {
			$args = [
				'resultCustomer' => $resultCustomer,
				'customers' => $customers
			];
			$this->retailcrm->customers()->combine($args);
		} catch (\Exception $e) {
			self::notifyAdmin($e);
		}
	}
}
