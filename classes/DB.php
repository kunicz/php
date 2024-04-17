<?

namespace php2steblya;

use php2steblya\Logger;

class DB
{
	private $pdo;
	private $error;
	private $db_host;
	private $db_database;
	private $db_username;
	private $db_password;
	private static $instance = null;

	public function __construct()
	{
		$this->db_host = $_ENV['DB_HOST'];
		$this->db_username = $_ENV['DB_USERNAME'];
		$this->db_database = $_ENV['DB_DATABASE'];
		$this->db_password = $_ENV['DB_PASSWORD'];
		$this->connect();
	}

	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * подключение к базе данных
	 */
	private function connect()
	{
		try {
			$this->pdo = new \PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_database . ';', $this->db_username, $this->db_password);
		} catch (\PDOException $e) {
			$this->error = $e->getMessage();
			$logger = Logger::getInstance();
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath(__FILE__));
			$logger->addToLog('db_connect_host', $this->db_host);
			$logger->addToLog('db_connect_username', $this->db_username);
			$logger->addToLog('db_connect_database', $this->db_database);
			$logger->addToLog('db_connect_password', $this->db_password);
			$logger->sendToAdmin();
		}
	}

	/**
	 * делаем запрос в базу данных
	 */
	public function sql($stmt, $params = [])
	{
		try {
			$stmt = trim(str_replace(["\r", "\n", "\t"], ' ', $stmt));
			$method = strstr($stmt, ' ', true);

			if (!in_array($method, ['SELECT', 'INSERT', 'UPDATE', 'DELETE'])) {
				throw new \PDOException('Unsupported query type: ' . $method);
			}

			$pdo = $this->pdo->prepare($stmt);
			switch ($method) {
				case 'SELECT':
					$pdo->execute();
					return $pdo->fetchAll(\PDO::FETCH_OBJ);
				case 'UPDATE':
				case 'INSERT':
				case 'DELETE':
					foreach ($params as $key => $value) {
						$pdo->bindValue(':' . $key, $value);
					}
					$pdo->execute();
					break;
			}
		} catch (\PDOException $e) {
			$this->error = $e->getMessage();
			$logger = Logger::getInstance();
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath(__FILE__));
			$logger->addToLog('db_sql_params', $params);
			$logger->addToLog('db_sql_stmt', $stmt);
			$logger->sendToAdmin();
		}
	}

	public function getError()
	{
		return $this->error;
	}
	public function hasError()
	{
		return $this->error;
	}
}
