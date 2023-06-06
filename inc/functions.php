<?
function writeFile($url, $text)
{
	/**
	 * пишем что-то в файл
	 */
	$file = fopen($url, 'w');
	fwrite($file, $text);
	fclose($file);
}
