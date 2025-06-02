<?

namespace php2steblya\api;

use php2steblya\interfaces\services\ModuleInterface;

abstract class ApiModule implements ModuleInterface
{
	protected ApiService $apiService;

	public function __construct(ApiService $apiService)
	{
		$this->apiService = $apiService;
	}

	public function request(string $method, string $endpoint, array $data = []): object
	{
		return $this->apiService->request($method, $endpoint, $data);
	}
}
