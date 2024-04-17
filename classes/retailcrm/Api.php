<?

namespace php2steblya\retailcrm;

class Api extends \php2steblya\Api
{
	public function __construct()
	{
		$this->token = $_ENV['API_RETAILCRM_TOKEN'];
		$this->adres = $_ENV['API_RETAILCRM_SITE'];
		$this->args = ['apiKey' => $this->token];
	}

	public function getError()
	{
		$errors = (array) $this->response->errors;
		$errorsMsg = [];
		foreach ($errors as $key => $value) {
			$errorsMsg[] = $key . ' : ' . $value;
		}
		return $this->response->errorMsg . (count($errorsMsg) ? ' : ' . implode(',', $errorsMsg) : '');
	}
	public function hasError()
	{
		return !$this->response->success;
	}
}
