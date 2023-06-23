<?
require_once __DIR__ . '/!autoload.php';

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
	$className = $args['script'];
	$class = 'php2steblya\\scripts\\' . $className;
	if (class_exists($class)) {
		$scriptInstance = new $class();
		$scriptInstance->init();
		die($scriptInstance->log->getJson());
	} else {
		die('script not found');
	}
} else {
	die('script not passed');
}
