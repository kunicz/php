<?php

namespace php2steblya;

use php2steblya\Exception;

/**
 * Класс для работы с файлами.
 */
class File
{
	private string $url; // путь к файлу
	private string $text = ''; // cодержимое файла

	public function __construct(string $url)
	{
		$this->url = $url;

		if (file_exists($url)) {
			$this->text = file_get_contents($url);
			if ($this->text === false) throw new \Exception("не удалось прочитать файл: $url");
		} else {
			$this->text = '';
		}
	}

	// записывает текст в файл, перезаписывая его содержимое.
	public function write(string $text): void
	{
		if (file_put_contents($this->url, $text) === false) throw new \Exception("не удалось записать в файл: $this->url");
		$this->text = $text;
	}

	// добавляет текст в конец файла.
	public function append(string $text): void
	{
		$newContent = $this->text . PHP_EOL . $text;
		if (file_put_contents($this->url, $newContent, FILE_APPEND) === false) throw new \Exception("не удалось добавить текст в файл: $this->url");
		$this->text .= PHP_EOL . $text;
	}

	// добавляет элемент в JSON-массив в указанном файле.
	// если файл не существует, он будет создан.
	public static function appendToArray(string $filePath, mixed $data): void
	{
		$items = [];
		if (file_exists($filePath)) {
			$content = file_get_contents($filePath);
			if ($content) {
				$items = json_decode($content, true);
				if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception("некорректный JSON в файле: $filePath");
			}
		}
		$items[] = $data;
		$content = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		if (file_put_contents($filePath, $content) === false) throw new \Exception("не удалось записать JSON в файл: $filePath");
	}

	// возвращает содержимое файла.
	public function getContents(): string
	{
		return $this->text;
	}

	// возвращает только смысловую часть пути к файлу, выкидывая серверную часть.
	public static function shortenPath(string $string): string
	{
		return str_replace($_ENV['PROJECT_PATH'] . '/classes/', '', $string);
	}
}
