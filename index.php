<?
require_once __DIR__ . '/!autoload.php';

use php2steblya\Script;

if (isset($_GET['script'])) {
	Script::initClass($_GET);
} else {
	header('Location: https://2steblya.ru/php');
	die();
}
