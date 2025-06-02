<?php

namespace php2steblya\scripts\tilda;

use php2steblya\Script;
use php2steblya\File;
use php2steblya\order\OrderData;

// написан для отработки вебхуов из Тильды
// так как настройками тильды запрещеноо разным вебхукам отсылаться на один и тот же endpoint
// вебхук после создания заказа - https://php.2steblya.ru/webhook?script=tilda/Order
// вебхук после оплаты заказа - https://php.2steblya.ru/webhook?script=tilda/Order&paid
// но ключ paid нужен только для различия между вебхуками и в логике не участвует (должен быть удален в cleaner)
// в самом скрипте проверка оплаты осуществляется за счет флага $od['payment']['recieved']
class Order extends Script
{
	protected array $od;
	protected bool $isTest;
	private array $mock;

	public function init(): void
	{
		$this->isTest = $this->isTest();
		$this->defineOrderData();

		// если это префлайт запрос к вебхуку от тильды, просто возвращаем
		// Script::success веренет http_response_code(200);
		if ($this->isTildaTest()) return;

		$this->prepareOrderData();
		$this->executeHandlers();
	}

	// определяет (бевой/тест) и валидирует изначальные данные заказа
	private function defineOrderData(): void
	{
		$this->logger->setGroup('od_raw');
		$this->od = $this->isTest ? $this->mock : $_POST;
		$this->logger->add('od', $this->od);
		$this->writeOrderFile();

		if (empty($this->od['shop_crm_id'])) throw new \Exception('не указан shop_crm_id');
		if (empty($this->od['payment']['products'])) throw new \Exception('заказ не содержит ни одного товара');
	}

	// получает данные заказа.
	private function prepareOrderData(): void
	{
		$this->logger->setGroup('od_prepared');
		$orderData = new OrderData($this->od, $this->script);
		$this->od = $orderData->prepare();
		$this->logger->add('od', $this->od);
	}

	// записывает заказ в файл.
	private function writeOrderFile(): void
	{
		$timestamp = date('Y-m-d-H-i-s');
		$name = $this->od['name_zakazchika'] ?? 'customer_' . uniqId();
		$name = mb_strtolower($name, 'UTF-8');
		$name = preg_replace('/[^a-zа-яё0-9_-]/iu', '_', $name);
		$name = preg_replace('/_+/', '_', $name);
		$name = trim($name, '_');
		$test = $this->isTest ? '_test' : '';
		$fileName = $timestamp;
		$fileName .= $test;
		$fileName .= '_' . $name;
		$fileName .= '.json';
		$filePath = $_ENV['PROJECT_PATH'] . '/logs/tilda_orders';
		$file = new File($filePath . '/' . $fileName);
		$file->write(json_encode($this->od, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}

	// запускает обработчики
	private function executeHandlers(): void
	{
		foreach ($this->getHandlerList() as $handler) {
			$this->runHandler($handler);
		}
	}

	// возвращает список обработчиков
	private function getHandlerList(): array
	{
		$handlers = ['db', 'telegram', 'retailcrm'];

		if ($this->isTest) {
			$testValue = trim((string)($this->scriptData['test'] ?? ''));
			if ($testValue !== '') {
				$handlersToTest = explode(',', $testValue);
				$handlersFiltered = array_values(array_intersect($handlers, $handlersToTest));
				if (empty($handlersFiltered)) throw new \Exception('Не передан ни один допустимый handler: ' . $testValue);
				$handlers = $handlersFiltered;
			}
			// если test есть, но не указаны хэндлеры (test=db,retailcrm), то запускаем все
		}

		$this->logger->setGroup('handlers')->add('data', $handlers);
		return $handlers;
	}

	// запускает обработчик
	private function runHandler(string $handler): void
	{
		$className = 'php2steblya\\order\\handlers\\' . ucfirst($handler);
		if (!class_exists($className)) {
			$this->logger->addError(new \Exception("handler-класс $handler не найден"));
			return;
		}
		$instance = new $className($this->od, $this->script);
		$instance->execute();
	}

	// проверяет, что это префлайт запрос к вебхуку от тильды
	private function isTildaTest(): bool
	{
		$is = !isset($this->od['formid']);
		$this->logger->setGroup('tilda_test')->add('is', $is);
		return $is;
	}

	// проверяет, что это тестовый запрос
	private function isTest(): bool
	{
		$is = isset($this->scriptData['test']);
		$this->logger->setGroup('test')->add('is', $is);
		if ($is) $this->defineMock();
		return $is;
	}

	// устанавливает mock-данные для тестирования
	private function defineMock(): void
	{
		$filePath = $_ENV['PROJECT_PATH'] . '/classes/order/mocks';
		$mock = $this->scriptData['mock'] ?? 'default';
		$path = $filePath . '/' . $mock . '.json';
		$this->logger->add('mock', basename($path));
		$this->logger->add('mock_path', $path);
		if (!file_exists($path)) throw new \Exception("не найден мок-файл");

		//чтобы тестировать заказ, можно использовать мок-файл с тестовыми данными
		//и добвлять/изменять поля вручную через $_POST
		$file = new File($path);
		$this->mock = json_decode($file->getContents(), true);
		foreach ($_POST as $key => $value) $this->mock[$key] = $value;

		$this->logger->add('mock_data', $this->mock);
	}
}
