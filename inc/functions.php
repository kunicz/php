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
function writeLog($text)
{
	/**
	 * пишем лог
	 */
	$file = fopen(dirname(__DIR__) . '/log/log-' . date('Ym') . '.txt', 'a');
	fwrite($file, date('Y-m-d H:i:s') . ': ' . $text . "\r\n");
	fclose($file);
}
function getLogSummary($log, $preambula, $result, $successDescr = '')
{
	if ($log['errors'] && count($log['errors'])) {
		return $preambula . ' не ' . $result . '(' . implode(',', $log['errors']) . ')';
	} else {
		return $preambula . ' ' . $result . ($successDescr ? ' (' . $successDescr . ')' : '');
	}
}
