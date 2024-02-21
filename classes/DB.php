<?

namespace php2steblya;

use \PDO;

class DB
{
	protected $pdo;
	protected $db_host;
	protected $db_database;
	protected $db_username;
	protected $db_password;

	public function __construct()
	{
		$this->db_host = $_ENV['DB_HOST'];
		$this->db_username = $_ENV['DB_USERNAME'];
		$this->db_database = $_ENV['DB_DATABASE'];
		$this->db_password = $_ENV['DB_PASSWORD'];
		$this->connect();
	}

	/**
	 * подключение к базе данных
	 */
	private function connect()
	{
		try {
			$this->pdo = new PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_database . ';', $this->db_username, $this->db_password);
		} catch (\PDOException $e) {
			var_dump($e->getMessage());
		}
	}

	/**
	 * делаем запрос в базу данных
	 */
	public function sql($stmt, $params = [])
	{
		try {
			$stmt = trim(str_replace(["\r", "\n", "\t"], ' ', $stmt));
			$method = strstr($stmt, ' ', true); //первое слово (SELECT,INSERT,UPDATE,DELETE)
			$stmt = $this->pdo->prepare($stmt);
			switch ($method) {
				case 'SELECT':
					$stmt->execute();
					return $stmt->fetchAll(PDO::FETCH_OBJ);
				case 'UPDATE':
				case 'INSERT':
				case 'DELETE':
					//если в массиве параметров массивы, то работаем с несколькими записями
					if (isset($params[0]) && is_array($params[0])) {
						foreach ($params as $param) {
							$stmt->execute($param);
						}
					} else {
						$stmt->execute($params);
					}
			}
		} catch (\PDOException $e) {
			var_dump($e->getMessage());
		}
	}
}
