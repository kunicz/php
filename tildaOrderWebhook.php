<?
require_once __DIR__ . '/inc/functions.php';
$order = print_r($_POST);
writeFile(__DIR__ . '/tildaOrderLast-2steblya.txt', $order);
die();
