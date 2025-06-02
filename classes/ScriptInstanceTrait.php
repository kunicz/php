<?php

namespace php2steblya;

use php2steblya\db\Db;
use php2steblya\db\DbService;
use php2steblya\Script;
use php2steblya\Logger;
use php2steblya\api\Api;
use php2steblya\api\ApiService;
use php2steblya\helpers\Ensure;

trait ScriptInstanceTrait
{
	// всегда доступны (инициализируются в конструкторе)
	protected ?string $site = null;
	protected array $scriptData = [];
	protected mixed $response = null;
	protected Script $script;

	// ленивая инициализация (инициализируются при первом обращении)
	private ?Logger $logger = null;
	private ?DbService $db = null;
	private ?ApiService $retailcrm = null;
	private ?ApiService $telegram = null;
	private ?ApiService $moysklad = null;
	private ?array $shops = null;

	public function __construct(array $_GET_scriptData)
	{
		$this->logger = Logger::getInstance();
		$this->scriptData = $this->buildScriptData($_GET_scriptData);
		$this->site = $this->scriptData['site'] ?? null;
		$this->script = $this;
	}

	// у каждого скрипта должен быть свой init()
	abstract public function init();

	// записывает результат выполнения скрипта в свойство
	public function setResponse($response): void
	{
		$this->response = $response;
	}

	// получает реультат выполнения скрипта из свойства
	public function getResponse(): mixed
	{
		return $this->response;
	}

	// возвращает массив с данными скрипта
	// схлопывает в единый массив все данные из $_GET и $_POST с проверкой на пересечения ключей
	private function buildScriptData(array $_GET_scriptData): array
	{
		$_POST_scriptData_raw = file_get_contents('php://input');
		$_POST_scriptData = Ensure::array($_POST_scriptData_raw);
		$this->logger->addRoot('script_data', [
			'$_GET' => $_GET_scriptData,
			'$_POST' => [
				'array' => $_POST_scriptData,
				'raw' => $_POST_scriptData_raw,
				'type' => gettype($_POST_scriptData_raw),
			]
		]);

		// Проверка на пересечение ключей
		$dup = array_intersect_key($_GET_scriptData, $_POST_scriptData);
		if (!empty($dup)) throw new \Exception('дублировать параметры $_GET и $_POST запрещено: ' . implode(', ', array_keys($dup)));

		return array_merge($_GET_scriptData, $_POST_scriptData);
	}

	// возвращает свойство, если оно уже инициализировано,
	// иначе инициализирует его и возвращает
	public function __get(string $name): mixed
	{
		return match ($name) {
			'db' => $this->db ??= Db::createService(),
			'shops' => $this->shops ??= $this->getShops(),
			'retailcrm' => $this->retailcrm ??= Api::createService('retailcrm'),
			'telegram'  => $this->telegram  ??= Api::createService('telegram'),
			'moysklad'  => $this->moysklad  ??= Api::createService('moysklad'),
			'logger' => $this->logger ??= Logger::getInstance(),
			default     => throw new \Exception("ошибка lazy-инициализации. неизвестное свойство '$name'"),
		};
	}

	// получает список магазинов со всеми данными из базы данных.
	private function getShops(): array
	{
		$shops = $this->__get('db')->shops()->get();
		if (empty($shops)) throw new \Exception("не удалось получить список магазинов.");
		return $shops;
	}
}
