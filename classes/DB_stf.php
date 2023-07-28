<?

namespace php2steblya;

class DB_stf extends DB
{
	public function __construct()
	{
		$this->db_host = $_ENV['DB_HOST'];
		$this->db_username = $_ENV['DB_USERNAME'];
		$this->db_database = $_ENV['DB_DATABASE'];
		$this->db_password = $_ENV['DB_PASSWORD'];
		parent::__construct();
	}

	/**
	 * получаем страны
	 */
	public function getCountries()
	{
		return $this->sql("
			SELECT *
			FROM countries
		", "$this->r стран");
	}

	/**
	 * получаем сотрудника
	 */
	public function getEmployee($key, $value)
	{
		return $this->sql("
			SELECT *,
			COALESCE(e.employee_id, c.employee_id, f.employee_id) AS employee_id
			FROM employees e
			LEFT JOIN couriers c ON e.employee_id = c.employee_id
			LEFT JOIN florists f ON e.employee_id = f.employee_id
			LEFT JOIN cities ci ON e.city_id = ci.city_id
			LEFT JOIN countries co ON co.country_id = ci.country_id
			LEFT JOIN banks b ON b.bank_id = e.bank_id
			LEFT JOIN stores s ON s.crm_store_id = f.crm_store_id
			WHERE e.$key = $value
		", "$this->r сотрудника");
	}

	/**
	 * получаем беседу бота с юзером
	 */
	public function getTelegramChat($bot, $chatId)
	{
		return $this->sql("
			SELECT *
			FROM telegram_chats_$bot
			WHERE id = $chatId
		", "$this->r беседы $chatId c $bot");
	}

	/**
	 * пишем беседу бота с юзером
	 */
	public function setTelegramChat($bot, $data)
	{
		$chatId = $data['chat_id'];
		$data = [
			'id' => $data['chat_id'],
			'updated' => date('Y.m.d H:i:s'),
			'state' => $data['state'] ?: 'start',
			'state_comment' => $data['state_comment'] ?: '',
			'user' => json_encode($data['user'] ? $data['user'] : []),
			'messages' => json_encode($data['messages'] ? $data['messages'] : [])
		];
		$this->log->push('data', $data);
		list($paramsKeys, $paramsKeysColon, $paramsKeysColonKeys, $paramsValues) = self::pdoParams($data);
		return $this->sql("
			INSERT INTO telegram_chats_$bot ($paramsKeys)
			VALUES ($paramsKeysColon)
    		ON DUPLICATE KEY UPDATE $paramsKeysColonKeys
		", "$this->u беседы $chatId с $bot", $paramsValues);
	}

	/**
	 * оформляем два массива с параметрами для INSERT,UPDATE,DELETE
	 * для дальнейшей передачи в функцию sql
	 */
	private static function pdoParams(array $data)
	{
		$p = [
			0 => '', 				//paramsKeys
			1 => '', 				//paramsKeysColon
			2 => '',				//paramsKeysColonKeys
			3 => $data			//paramsValues
		];
		foreach (array_keys($data) as $key) {
			$p[0] .= ($p[0] ? ', ' : '') . $key;
			$p[1] .= ($p[1] ? ', :' : ':') . $key;
			$p[2] .= ($p[2] ? ', ' : '') . $key . ' = :' . $key;
		}
		return $p;
	}
}
