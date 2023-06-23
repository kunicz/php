<?

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__FILE__));
$dotenv->load();

function allowed_sites(): array
{
	//айдишники разрешенных сайтов
	return explode(',', $_ENV['allowed_sites']);
}
function castrated_items(): array
{
	//названия товаров, для которых не нужно совершать дополнительные манипуляции
	return explode(',', $_ENV['castrated_items']);
}
