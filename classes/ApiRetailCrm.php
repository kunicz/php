<?

namespace php2steblya;

class ApiRetailCrm extends Api
{
	public function __construct()
	{
		$this->token = $_ENV['API_RETAILCRM_TOKEN'];
		$this->adres = $_ENV['API_RETAILCRM_SITE'];
	}
	public function getCount()
	{
		return $this->response->pagination->totalCount;
	}
	public function getPageCount()
	{
		return $this->response->pagination->totalPageCount;
	}
	public function getCurrentPage()
	{
		return $this->response->pagination->currentPage;
	}
	public function getError()
	{
		$error = $this->response->errorMsg;
		return $error;
	}
	public function hasErrors()
	{
		return !$this->response->success;
	}
}
