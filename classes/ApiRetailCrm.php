<?

namespace php2steblya;

class ApiRetailCrm extends Api
{
	public function __construct()
	{
		$this->adres = $_ENV['API_SITE'];
		$this->token = $_ENV['API_TOKEN'];
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
		if ($this->response->errors) $error .= ' (' . str_replace('=', ': ', str_replace('+', ' ', http_build_query($this->response->errors, '', '+'))) . ')';
		return $error;
	}
	public function hasErrors()
	{
		return !$this->response->success;
	}
}
