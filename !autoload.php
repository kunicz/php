<?

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__FILE__));
$dotenv->load();

/**
 * айдишники разрешенных сайтов
 */
function allowed_sites(): array
{
	return explode(',', $_ENV['allowed_sites']);
}

/**
 * названия товаров, для которых не нужно совершать дополнительные манипуляции
 */
function castrated_items(): array
{
	return [
		'ДОНАТОШНАЯ'
	];
}
