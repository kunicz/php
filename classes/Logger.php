<?

namespace php2steblya;

use php2steblya\File;

class Logger
{
	private static ?self $instance = null;
	private array $logData; // Массив для хранения логов
	private array $groupStack = []; // Стек ключей групп для навигации

	private function __construct()
	{
		$this->logData = ['errors' => [], 'process' => []];
	}

	// возвращает единственный экземпляр класса Logger.
	public static function getInstance(): self
	{
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}

	// получает ссылку на группу (можно передать кол-во уровней вверх по стеку)
	private function &getGroup(int $pop = 0): array
	{
		$target = &$this->logData['process'];
		for ($i = 0; $i < count($this->groupStack) - $pop; $i++) {
			$target = &$target[$this->groupStack[$i]];
		}
		return $target;
	}

	// получает ссылку на текущую группу
	private function &getCurrentGroup(): array
	{
		return $this->getGroup();
	}

	// получает ссылку на родительскую группу
	private function &getParentGroup(): array
	{
		return $this->getGroup(1);
	}

	// открывает группу логов.
	public function setGroup(string $groupTitle, bool $isSub = false): Logger
	{
		$title = $groupTitle;
		if (!$isSub || empty($this->groupStack)) {
			$this->groupStack = [$title];
			$this->logData['process'][$title] = [];
		} else {
			$this->groupStack[] = $title;
			$parent = &$this->getParentGroup();
			$parent[$title] = [];
		}
		return $this;
	}

	// завершает текущую группу логов.
	public function exitGroup(bool $isSub = false): Logger
	{
		if ($isSub && empty($this->groupStack)) return $this;

		if ($isSub) {
			$this->groupStack = array_slice($this->groupStack, 0, -1);
		} else {
			$this->groupStack = [array_pop($this->groupStack)];
		}
		return $this;
	}

	// открывает подгруппу логов.
	public function setSubGroup(string $groupTitle): Logger
	{
		return $this->setGroup($groupTitle, true);
	}

	// закрывает текущую подгруппу логов.
	public function exitSubGroup(): Logger
	{
		return $this->exitGroup(true);
	}

	// добавляет лог в текущую группу или подгруппу.
	public function add(string $key, mixed $value = null): Logger
	{
		$target = &$this->getCurrentGroup();
		$target[$key] = $value;
		return $this;
	}

	// добавляет ошибку в лог.
	public function addError(\Throwable $e): Logger
	{
		$data = [
			'msg' => $e->getMessage(),
			'file' => File::shortenPath($e->getFile()),
			'line' => $e->getLine(),
			'group' => $this->groupStack[0] ?? 'root'
		];
		$this->logData['errors'][] = $data;
		return $this;
	}

	// добавляет информацию о том, какой хэндлер отловил ошибку.
	public function addErrorHandler(string $e): Logger
	{
		$this->logData['errors']['handler'] = $e;
		return $this;
	}

	// добавляет лог в корень.
	public function addRoot(string $key, mixed $value = null): Logger
	{
		$this->logData[$key] = $value;
		return $this;
	}

	// возвращает ошибки.
	public function getErrors(): array
	{
		return $this->logData['errors'];
	}

	// возвращает текущие логи.
	public function getLogData(): array
	{
		return $this->logData;
	}
}
