<?
require_once __DIR__ . '/!autoload.php';

if (isset($_GET['script'])) {
	$className = $_GET['script'];
	$class = 'php2steblya\\scripts\\' . $className;
	if (class_exists($class)) {
		switch ($className) {
			case 'TildaOrderWebhook':
				http_response_code(200);
				if (isset($_GET['site'])) $scriptInstance = new $class($_GET['site'], isset($_GET['payed']) ? true : false, isset($_GET['testMode']) ? true : false); //site, payed, testMode
				break;
			default:
				$scriptInstance = new $class();
		}
		$scriptInstance->init();
		die($scriptInstance->log->getJson());
	} else {
		die('script not found');
	}
} else {
	header('Location: https://2steblya.ru/php');
	die();
}
