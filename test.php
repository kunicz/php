<?

header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

require_once __DIR__ . '/!autoload.php';

use php2steblya\Script;

if (isset($_GET['script'])) {
	Script::initClass($_GET);
} else {
	header('Location: https://2steblya.ru/php');
	die();
}
