<?

namespace php2steblya;

class OrderData_analytics
{
	public array $yandex;
	public array $utm;
	public $otkudaUznal;

	public function __construct()
	{
		$this->yandex = [
			'clientId' => ''
		];
		$this->utm = [
			'source' => '',
			'medium' => '',
			'campaign' => '',
			'content' => '',
			'term' => ''
		];
	}
	public function setOtkudaUznal($data)
	{
		$this->otkudaUznal = $data;
	}
	public function setYandexClientId($data)
	{
		$this->yandex['clientId'] = $data;
	}
	public function setUtmSource($data)
	{
		$this->utm['source'] = $data;
	}
	public function setUtmMedium($data)
	{
		$this->utm['medium'] = $data;
	}
	public function setUtmCampaign($data)
	{
		$this->utm['campaign'] = $data;
	}
	public function setUtmContent($data)
	{
		$this->utm['content'] = $data;
	}
	public function setUtmTerm($data)
	{
		$this->utm['term'] = $data;
	}
}
