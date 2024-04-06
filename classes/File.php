<?

namespace php2steblya;

class File
{
	private $url;
	private $text;
	private $file;

	public function __construct(string $url)
	{
		$this->url = $url;
		$this->text = file_get_contents($url);
	}
	public function write(string $text)
	{
		$this->file = fopen($this->url, 'w');
		$this->text = $text;
		fwrite($this->file, $text);
		fclose($this->file);
	}
	public function append(string $text)
	{
		$this->file = fopen($this->url, 'a');
		$this->text .= "\r\n" . $text;
		fwrite($this->file, "\r\n" . $text);
		fclose($this->file);
	}
	public static function appendToArray(string $filePath, $data)
	{
		$file = new self($filePath);
		$items = $file->getContents();
		if ($items) {
			$items = json_decode($items, true);
		} else {
			$items = [];
		}
		$items[] = $data;
		$file->write(json_encode($items));
	}
	public function getContents()
	{
		return $this->text;
	}
}
