<?

namespace php2steblya;

class ApiTelegram extends Api
{
	public function __construct()
	{
		$this->token = $_ENV['TELEGRAM_BOT_TOKEN'];
		$this->adres = 'https://api.telegram.org/bot' . $this->token;
	}
	public function getError()
	{
		$error = $this->response->description;
		return $error;
	}
	public function hasErrors()
	{
		return !$this->response->ok;
	}
}
