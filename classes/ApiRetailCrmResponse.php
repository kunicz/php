<?

namespace php2steblya;

use php2steblya\LoggerException as Exception;
use php2steblya\ApiRetailCrm as Api;

class ApiRetailCrmResponse
{
	public $log;
	public $api;
	protected $args;
	protected $site;
	protected $method;
	protected $source;
	public $response;

	public function __construct($source)
	{
		$this->source = $source;
		$this->log->push('parent source', $this->source);
	}

	public function request($getpost)
	{
		$this->log->push('method', $this->method);
		$this->log->push('queryString', $this->args);
		$this->log->push('response', $this->api->response);
		try {
			$this->api = new Api();
			$this->api->curl($getpost, $this->method, $this->args);
			if ($this->api->hasErrors()) {
				throw new Exception('заказ не создан');
			}
			$this->response = $this->api->response;
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}

	public function getLog()
	{
		return $this->log->get();
	}
}
