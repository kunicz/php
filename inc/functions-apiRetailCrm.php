<?
require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

function apiUrl($method, $args)
{
	return $_SERVER['API_SITE'] . '/' . $method . '?' . apiUrlArgs($args);
}
function apiUrlArgs($args)
{
	$query = ['apiKey=' . $_SERVER['API_TOKEN']];
	foreach ($args as $key => $value) {
		$query[] = $key . '=' . $value;
	}
	return implode('&', $query);
}
function apiGET($method, $args = [])
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_URL, apiUrl($method, $args));
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$server_output = curl_exec($ch);
	curl_close($ch);
	return json_decode($server_output);
}
function apiPOST($method, $args = [])
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, apiUrl($method, $args));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, apiUrlArgs($args));
	$server_output = curl_exec($ch);
	curl_close($ch);
	return json_decode($server_output);
}
function apiErrorLog(&$log, $apiObject, $descr = '')
{
	if ($apiObject->success) return;
	$log['errors'][] = ($descr ? $descr . ' : ' : '') . $apiObject->error->code . ' : ' . $apiObject->error->message;
}
