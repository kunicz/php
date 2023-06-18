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
	private $contentsKey;

	public function __construct($source = '')
	{
		$this->source = $source;
		$this->contentsKey = null;
		$this->errors = [];
		$this->contents = [];
	}
	public function setSource($data)
	{
		$this->source = $data;
	}

	/**
	 * summary
	 */
	private function buildSummary()
	{
		$this->summary = date('Y-m-d H:i:s');
		$this->summary .= ($this->hasErrors() ? ' | fail | ' : ' | success | ');
		$this->summary .= (isset($this->contents['parent source']) ? $this->contents['parent source'] . ' : ' : '') . $this->source;
		if ($this->hasErrors()) {
			$this->summary .= ' | ' . implode(',', $this->errors);
		} else {
			if (!$this->summaryRemark) return;
			$this->summary .= ' | ' . $this->summaryRemark;
		}
	}
	public function writeSummary()
	{
		$this->buildSummary();
		$url = dirname(dirname(__FILE__)) . '/log/log-' . date('Ym') . '.txt';
		$file = new File($url);
		$file->append($this->summary);
	}
	/**
	 * errors
	 */
	public function pushError($error)
	{
		$this->errors[] = $error;
	}
	private function hasErrors()
	{
		return !empty($this->errors);
	}
	/**
	 * comments
	 */
	public function pushNote($note)
	{
		$this->contents['notes'][] = $note;
	}
	/**
	 * contents
	 */
	public function insert($key)
	{
		$this->contentsKey = $key;
		if (isset($this->contents[$key])) return;
		$this->contents[$key] = [];
	}
	public function switch($key)
	{
		$this->contentsKey = $key;
	}
	public function push($key, $data)
	{
		if (!$this->contentsKey) {
			$this->contents[$key] = $data;
		} else {
			$this->contents[$this->contentsKey][$key] = $data;
		}
	}
	public function setRemark($remark)
	{
		if (is_array($remark)) $remark = implode(',', $remark);
		$this->summaryRemark = $remark;
	}
	/**
	 * returning
	 */
	public function get()
	{
		$log = [
			'source' => $this->source,
			'errors' => $this->errors,
		];
		if ($this->summaryRemark) $log['remark'] = $this->summaryRemark;
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
