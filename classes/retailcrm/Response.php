<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;
use php2steblya\retailcrm\Api;

class Response
{
	protected ?object $response;
	protected array $retailcrmArgs;
	protected string $retailcrmMethod;
	private $api;

	public function request($httpMethod)
	{
		try {
			$this->api = new Api();
			$this->api->curl($httpMethod, $this->retailcrmMethod, $this->retailcrmArgs);
			$this->response = $this->api->getResponse();
			if ($this->api->hasError()) throw new \Exception($this->api->getError());
		} catch (\Exception $e) {
			$logger = Logger::getInstance();
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			$logger->addToLog('retailcrm_response_method', $this->retailcrmMethod . ' (' . $httpMethod . ')');
			$logger->addToLog('retailcrm_response_args', $this->retailcrmArgs);
			$logger->addToLog('retailcrm_response_response', $this->response);
			$logger->sendToAdmin();
		}
	}

	public function getError()
	{
		return $this->api->getError();
	}
	public function hasError()
	{
		return $this->api->hasError();
	}
	public function getTotalCount()
	{
		return $this->response->pagination->totalCount;
	}
	public function getTotalPageCount()
	{
		return $this->response->pagination->totalPageCount;
	}
	public function getCurrentPage()
	{
		return $this->response->pagination->currentPage;
	}
}
