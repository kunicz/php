<?

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function apiUrl($method, $args)
{
	$url = $_SERVER['API_SITE'] . '/' . $method . '?' . setApiUrlArgs($args) . '&apiKey=' . $_SERVER['API_TOKEN'];
	return $url;
}
function setApiUrlArgs($args)
{
	$query = [];
	foreach ($args as $key => $value) {
		$query[] = $key . '=' . $value;
	}
	return implode('&', $query);
}

function apiGET($method, $args)
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
	return $server_output;
}

function apiPOST($method, $args)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, apiUrl($method, $args));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, setApiUrlArgs($args));
	$server_output = curl_exec($ch);
	curl_close($ch);
	return $server_output;
}
