<?

namespace php2steblya;

use php2steblya\Logger;
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
		$this->api = new Api();
		$this->api->curl($getpost, $this->method, $this->args);
		$this->log->push('method', $this->method);
		$this->log->push('queryString', $this->args);
		$this->log->push('response', $this->api->response);
		if ($this->api->hasErrors()) {
			$this->log->pushError($this->api->getError());
			$this->abort();
		}
		$this->response = $this->api->response;
	}
	private function abort()
	{
		$this->log->writeSummary();
		die($this->log->getJson());
	}
	public function getLog()
	{
		return $this->log->get();
	}
}
