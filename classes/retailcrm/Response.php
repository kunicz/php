<?

namespace php2steblya\retailcrm;

use php2steblya\Logger;
use php2steblya\retailcrm\Api;

class Response
{
	protected ?object $response;
	protected array $retailcrmArgs;
	protected string $retailcrmMethod;

	public function request($httpMethod)
	{
		try {
			$api = new Api();
			$api->curl($httpMethod, $this->retailcrmMethod, $this->retailcrmArgs);
			$this->response = $api->getResponse();
			if ($api->hasErrors()) throw new \Exception($api->getError());
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
