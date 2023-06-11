<?

namespace php2steblya;

use php2steblya\File;

class Logger
{
	private $source;
	private $summary;
	private $summaryRemark;
	private array $errors;
	private array $contents;

	public function __construct(string $source, string $summaryRemark = '')
	{
		$this->source = $source;
		$this->summaryRemark = $summaryRemark;
		$this->errors = [];
		$this->contents = [];
	}
	public function getErrors()
	{
		return $this->errors;
	}
	public function pushError($error)
	{
		$this->errors[] = $error;
	}
	private function hasErrors()
	{
		return !empty($this->errors);
	}
	public function push($key, $data, $nested = false)
	{
		if ($nested === false) {
			$this->contents[$key] = $data;
		} else {
			$this->contents[$nested][$key] = $data;
		}
	}
	public function setRemark($remark)
	{
		if (is_array($remark)) $remark = implode(',', $remark);
		$this->summaryRemark = $remark;
	}
	private function buildSummary()
	{
		$this->summary = date('Y-m-d H:i:s');
		$this->summary .= ($this->hasErrors() ? ' | fail | ' : ' | success | ');
		$this->summary .= $this->source;
		if ($this->hasErrors()) {
			$this->summary .= ' (' . implode(',', $this->errors) . ')';
		} else {
			if (!$this->summaryRemark) return;
			$this->summary .= ' (' . $this->summaryRemark . ')';
		}
	}
	public function getSummary()
	{
		$this->buildSummary();
		return $this->summary;
	}
	public function writeSummary()
	{
		$url = dirname(dirname(__FILE__)) . '/log/log-' . date('Ym') . '.txt';
		$file = new File($url);
		$file->append($this->getSummary());
	}
	public function get()
	{
		$log = [
			'source' => $this->source,
			'errors' => $this->errors
		];
		foreach ($this->contents as $key => $value) {
			$log[$key] = $value;
		}
		return $log;
	}
	public function getJson()
	{
		return json_encode($this->get());
	}
	public function print()
	{
		return print_r($this->get(), true);
	}
	public function dump()
	{
		echo '<pre>';
		var_dump($this->get());
		echo '</pre>';
	}
}
