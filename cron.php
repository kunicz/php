<?
require_once __DIR__ . '/!autoload.php';

use php2steblya\Script;

$args = [];
foreach ($argv as $arg) {
	if (strpos($arg, '=') !== false) {
		list($name, $value) = explode('=', $arg, 2);
		$args[$name] = $value;
	}
}
if (empty($args)) {
	die('no arguments passed');
}
if (isset($args['script'])) {
	Script::initClass($args);
} else {
	die('script not passed');
}
