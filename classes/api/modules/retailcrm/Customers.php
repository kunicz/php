<?php

namespace php2steblya\api\modules\retailcrm;

use php2steblya\Script;
use php2steblya\api\ApiModule;
use php2steblya\helpers\Ensure;
use php2steblya\helpers\Validate;

class Customers extends ApiModule
{
	public function get(array $data = []): object
	{
		$data['limit'] = $data['limit'] ?? 100;
		$data['page'] = $data['page'] ?? 1;
		return $this->request('GET', 'customers', $data);
	}

	public function getAll(array $data = []): object
	{
		$return = new \stdClass();
		$return->customers = [];
		$return->pagination = (object)[
			'currentPage' => 0,
			'totalPageCount' => 1,
		];

		while ($return->pagination->currentPage < $return->pagination->totalPageCount) {
			try {
				$data['page'] = $return->pagination->currentPage + 1;
				$apiResponse = $this->get($data);

				$return->customers = array_merge($return->customers, $apiResponse->customers);
				$return->pagination->currentPage = $apiResponse->pagination->currentPage;
				$return->pagination->totalPageCount = $apiResponse->pagination->totalPageCount;
			} catch (\Exception $e) {
				$return->pagination->currentPage++;
			}
		}

		return $return;
	}

	public function getById(array $data = []): object
	{
		Validate::notEmpty($data['id'], 'не передан ID клиента');
		return $this->request('GET', "customers/{$data['id']}");
	}

	public function edit(array $data = []): object
	{
		Validate::notEmpty($data['id'], 'не передан ID клиента');
		Validate::notEmpty($data['args'], 'не переданы данные для обновления клиента');
		Validate::notEmpty($data['args']['customer'], 'не переданы данные клиента для обновления');
		Validate::notEmpty($data['args']['site'], 'не указан сайт клиента');

		$data['args']['by'] = $data['args']['by'] ?? 'id';
		$data['args']['customer'] = Ensure::json($data['args']['customer']);

		return $this->request('POST', "customers/{$data['id']}/edit", $data['args']);
	}

	public function combine(array $data = []): object
	{
		Validate::notEmpty($data['customers'], 'не передан массив поглощаемых клиентов');
		Validate::notEmpty($data['resultCustomer'], 'не передан объект поглощающего клиента');
		Validate::notEmpty($data['resultCustomer']->id, 'не передан id поглощающего клиента');

		foreach ($data['customers'] as $index => $customer) {
			try {
				Validate::notEmpty($customer->id, "не передан id для поглощаемого клиента $index");
			} catch (\Exception $e) {
				unset($data['customers'][$index]);
				Script::notifyAdmin($e);
			}
		}

		Validate::notEmpty($data['customers'], 'массив клиентов для поглощения пуст');

		$args = [
			'resultCustomer' => Ensure::json($data['resultCustomer']),
			'customers' => Ensure::json(array_values($data['customers'])),
		];

		return $this->request('POST', 'customers/combine', $args);
	}
}
