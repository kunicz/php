<?

namespace php2steblya;

use \PDO;
use php2steblya\Logger;

class DB
{
	public $log;
	protected $c;
	protected $r;
	protected $u;
	protected $d;
	protected $pdo;
	protected $db_host;
	protected $db_database;
	protected $db_username;
	protected $db_password;

	public function __construct()
	{
		$this->c = 'создание';
		$this->r = 'получение';
		$this->u = 'обновление';
		$this->d = 'удаление';
		$this->log = new Logger('DB connect');
		$this->connect();
	}

	/**
	 * подключение к базе данных
	 */
	private function connect()
	{
		$source = 'подключение к базе данных';
		$this->log->push('database', $this->db_database);
		try {
			$this->pdo = new PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_database . ';', $this->db_username, $this->db_password);
		} catch (\PDOException $e) {
			$this->pdoError($e, $source);
		}
	}

	/**
	 * делаем запрос в базу данных
	 */
	protected function sql($stmt, $source, $params = [])
	{
		try {
			$stmt = trim(str_replace(["\r", "\n", "\t"], ' ', $stmt));
			$this->log->push('stmt', $stmt);
			$method = strstr($stmt, ' ', true); //первое слово (SELECT,INSERT,UPDATE,DELETE)
			$this->log->push('method', $method);
			$stmt = $this->pdo->prepare($stmt);
			switch ($method) {
				case 'SELECT':
					$stmt->execute();
					$data = $stmt->fetchObject();
					$this->log->push($source, $data);
					return $data;
				case 'UPDATE':
				case 'INSERT':
				case 'DELETE':
					foreach ($params as $key => $value) {
						$stmt->bindValue(':' . $key, $value);
					}
					$stmt->execute();
					$this->log->push($source, true);
					return true;
			}
		} catch (\PDOException $e) {
			$this->pdoError($e, $source);
		}
	}

	/**
	 * в случае PDOException пишем ошибку в лог и убиваем скрипт
	 */
	private function pdoError($e, $source)
	{
		$error = $source . ' (' . $this->db_database . ') | ' . $e->getMessage();
		$this->log->pushError($error);
		$this->log->writeSummary();
		die('fail | ' . $error);
	}

	public function getLog()
	{
		return $this->log->get();
	}
}
