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
function print_r_reverse($in)
{
	$lines = explode("\n", trim($in));
	if (trim($lines[0]) != 'Array') {
		// bottomed out to something that isn't an array
		return $in;
	} else {
		// this is an array, lets parse it
		if (preg_match("/(\s{5,})\(/", $lines[1], $match)) {
			// this is a tested array/recursive call to this function
			// take a set of spaces off the beginning
			$spaces = $match[1];
			$spaces_length = strlen($spaces);
			$lines_total = count($lines);
			for ($i = 0; $i < $lines_total; $i++) {
				if (substr($lines[$i], 0, $spaces_length) == $spaces) {
					$lines[$i] = substr($lines[$i], $spaces_length);
				}
			}
		}
		array_shift($lines); // Array
		array_shift($lines); // (
		array_pop($lines); // )
		$in = implode("\n", $lines);
		// make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one)
		preg_match_all("/^\s{4}\[(.+?)\] \=\> /m", $in, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		$pos = array();
		$previous_key = '';
		$in_length = strlen($in);
		// store the following in $pos:
		// array with key = key of the parsed array's item
		// value = array(start position in $in, $end position in $in)
		foreach ($matches as $match) {
			$key = $match[1][0];
			$start = $match[0][1] + strlen($match[0][0]);
			$pos[$key] = array($start, $in_length);
			if ($previous_key != '') $pos[$previous_key][1] = $match[0][1] - 1;
			$previous_key = $key;
		}
		$ret = array();
		foreach ($pos as $key => $where) {
			// recursively see if the parsed out value is an array too
			$ret[$key] = print_r_reverse(substr($in, $where[0], $where[1] - $where[0]));
		}
		return $ret;
	}
}
