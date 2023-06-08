<?

namespace php2steblya;

class File
{
	public $url;
	public $text;
	private $file;

	public function __construct(string $url)
	{
		$this->url = $url;
	}
	public function write(string $text)
	{
		$this->open('w');
		fwrite($this->file, $text);
		$this->close();
	}
	public function append(string $text)
	{
		$this->open('a');
		fwrite($this->file, "\r\n" . $text);
		$this->close();
	}
	private function open(string $method)
	{
		$this->file = fopen($this->url, $method);
	}
	private function close()
	{
		fclose($this->file);
	}
}
