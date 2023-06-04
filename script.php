<?
require __DIR__ . '/cors-headers.php';
require __DIR__ . '/functions-api.php';

$data = apiGET('customers', ['filter[name]' => '']);
echo json_encode($data);
die();
