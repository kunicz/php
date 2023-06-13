<?

header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

require_once __DIR__ . '/!autoload.php';

if (isset($_GET['script'])) {
	$className = $_GET['script'];
	$class = 'php2steblya\\scripts\\' . $className;
	if (class_exists($class)) {
		switch ($className) {
			case 'TildaOrderWebhook':
				$scriptInstance = new $class($_GET['site'], isset($_GET['testMode']) ? true : false); //site, testMode
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
