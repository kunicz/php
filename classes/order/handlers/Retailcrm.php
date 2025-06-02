<?php

namespace php2steblya\order\handlers;

use php2steblya\order\handlers\Abstract_handler as OrderHandler;
use php2steblya\order\retailcrm\Splitter;
use php2steblya\order\retailcrm\Mapper;

class Retailcrm extends OrderHandler
{
	public function execute()
	{
		//if (empty($this->od['payment']['recieved'])) return;

		// получаем разделенные и подготовленные для добавления в CRM блоки данных
		$crm_ods = Splitter::execute($this->od, $this->script);
		$this->logger->add('crm_ods_raw', $crm_ods);

		// конвертируем в формат, который принимает CRM
		$crm_ods = Mapper::execute($crm_ods, $this->script);
		$this->logger->add('crm_ods_prepared', $crm_ods);

		// обращаемся к API и добавляем заказы
		$this->logger->setSubGroup('creating_orders');
		foreach ($crm_ods as $crm_od) {
			$this->logger->setSubGroup($crm_od['externalId']);
			$this->vaidateData($crm_od);
			try {
				$args = [
					'site' => $crm_od['site'],
					'order' => json_encode($crm_od)
				];
				$this->script->retailcrm->orders()->create($args);
			} catch (\Exception $e) {
				$this->logger->addError($e);
			}
			$this->logger->exitSubGroup();
		}
		$this->logger->exitSubGroup();
	}

	private function vaidateData($data): void
	{
		if (empty($data['site'])) throw new \Exception('Не указан код магазина');
		if (empty($data['managerId'])) throw new \Exception('Не указан id менеджера магазина');
		if (empty($data['phone'])) throw new \Exception('Не указан номер телефона заказчика');
		if (empty($data['firstName'])) throw new \Exception('Не указано имя заказчика');
		if (empty($data['items'])) throw new \Exception('Заказ не содержит товаров');
	}
}
