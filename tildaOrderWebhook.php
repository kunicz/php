<?
require __DIR__ . '/inc/functions.php';

writeFile(__DIR__ . '/tildaLastOrder.txt', json_encode($_POST));
die();
