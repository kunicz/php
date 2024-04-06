<?

namespace php2steblya;

use php2steblya\Logger;

abstract class Api
{
	protected array $args = [];
	protected string $adres = '';
	protected string $token = '';
	protected ?object $response = null;

	public function curl(string $httpMethod, string $method, array $args): void
	{
		$queryString = $this->buildQueryString($args);
		//$logger = Logger::getInstance();
		//$logger->addToLog('api_query_string', $queryString);

		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false, // Consider removing this if possible, as it disables SSL certificate verification
			CURLOPT_AUTOREFERER    => true,
			CURLOPT_FOLLOWLOCATION => true
		]);

		switch ($httpMethod) {
			case 'get':
				curl_setopt_array($ch, [
					CURLOPT_HEADER => false,
					CURLOPT_URL    => $this->adres . '/' . $method . '?' . $queryString
				]);
				break;
			case 'post':
				curl_setopt_array($ch, [
					CURLOPT_POST          => true,
					CURLOPT_POSTFIELDS    => $queryString,
					CURLOPT_URL           => $this->adres . '/' . $method,
					CURLOPT_HTTPHEADER    => ['Content-Type: application/x-www-form-urlencoded']
				]);
				break;
			default:
				break;
		}

		try {
			$response = curl_exec($ch);
			if ($response === false) {
				throw new \Exception('curl error (' . curl_errno($ch) . '): ' . curl_error($ch));
			}
			$this->response = json_decode($response);
			if ($this->response === null) {
				throw new \Exception('API response is not array: ' . $response);
			}
		} catch (\Exception $e) {
			$logger = Logger::getInstance();
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath(__FILE__));
			$logger->addToLog('api_http_method', $httpMethod);
			$logger->addToLog('api_queryString', $queryString);
			$logger->addToLog('api_url', $this->adres . '/' . $method);
			$logger->sendToAdmin();
		} finally {
			curl_close($ch);
		}
	}

	private function buildQueryString(array $args): string
	{
		$this->args = array_merge($this->args, $args);
		return http_build_query($this->args);
	}

	public function getResponse()
	{
		return $this->response;
	}
}
