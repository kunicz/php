<?

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__FILE__));
$dotenv->load();

function allowed_sites(): array
{
	return explode('&&', $_SERVER['allowed_sites']);
}
