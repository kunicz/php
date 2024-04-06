<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\Logger;

abstract class Script
{
	protected $db;
	protected $site;
	protected $logger;

	/**
	 * функция получает сайты (магазины) из базы данных
	 * если вызывается без аргументов, то возвращает массив из БД
	 * если передан аргумент $param "id" или "code", то возвращает массив id или кодов
	 *
	 * @param  string $param code/id
	 * @return array
	 */
	protected function getSitesFromDB(string $param = null): array
	{
		try {
			if (!$this->db) $this->db = DB::getInstance();
			$sites = $this->db->sql("SELECT * FROM shops");
			if (empty($sites)) throw new \Exception('getSitesFromDB : sites not found in DB');
			switch ($param) {
				case 'id':
				case 'code':
					$sitesParam = [];
					foreach ($sites as $site) {
						$sitesParam[] = $site->{'shop_crm_' . $param};
					}
					return $sitesParam;
				default:
					return $sites;
			}
		} catch (\Exception $e) {
			$this->logger = Logger::getInstance();
			$this->logger->addToLog('error_message', $e->getMessage());
			$this->logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			$this->logger->sendToAdmin();
		}
	}

	/**
	 * функция получает сайт (магазин) из базы данных
	 * передавать надо массив ['id'=>$id] или ['code'=>$code] 
	 *
	 * @param  array $param
	 * @return array
	 */
	protected function getSiteFromDB(array $param): array
	{
		try {
			if (!$this->db) $this->db = DB::getInstance();
			$key = array_keys($param)[0];
			if (!in_array($key, ['id', 'code'])) throw new \Exception("getSiteFromDB : wrong key ($key)");
			$sites = $this->db->sql("SELECT * FROM shops WHERE shop_crm_{$key} = '{$param[$key]}'");
			if (empty($sites)) throw new \Exception('getSiteFromDB : sites not found in DB');
			return $sites;
		} catch (\Exception $e) {
			$this->logger = Logger::getInstance();
			$this->logger->addToLog('error_message', $e->getMessage());
			$this->logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			$this->logger->sendToAdmin();
		}
	}

	protected function isSiteExists()
	{
		$sitesFromDB = $this->getSitesFromDB();
		if (empty($sitesFromDB)) return false;
		foreach ($sitesFromDB as $siteFromDB) {
			if ($this->site === $siteFromDB->shop_crm_code) return true;
		}
		return false;
	}
}
