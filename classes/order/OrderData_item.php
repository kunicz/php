<?

namespace php2steblya\order;

use php2steblya\DB;
use php2steblya\Logger;

class OrderData_item
{
	public static function getOffer($product)
	{
		try {
			$db = DB::getInstance();
			$response = $db->sql("SELECT catalog FROM tilda_yml_catalog WHERE shop_crm_id = '{$product['shop_crm_id']}'");
			if (empty($response)) {
				throw new \Exception("catalog not found for shop ({$product['shop_crm_id']})");
			}
			$catalog = json_decode($response[0]->catalog, true);
			foreach ($catalog['offers'] as $catalogOffer) {
				if ($catalogOffer['vendorCode'] != $product['sku']) continue;
				return [
					'externalId' => $catalogOffer['id']
				];
			}
			throw new \Exception("sku ({$product['sku']}) not found in catalog");
		} catch (\Exception $e) {
			$logger = Logger::getInstance();
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath($e->getFile()));
			//$logger->sendToAdmin();
			return [];
		}
	}

	public static function getProperties($product)
	{
		$props = [];
		foreach ($product['options'] as $prop) {
			$name = htmlspecialchars_decode($prop['option']);
			$name = str_replace('?', '', $name); //удаляем ? в названиях опций (скока? какой цвет?)
			$value = preg_replace('/\s*\([^)]+\)/', '', $prop['variant']); //удаляем все, что в скобках
			if (!$value) continue;
			$props[] = [
				'name' => $name,
				'value' => $value
			];
		}
		//нитакой,индрошив,донат
		if (in_array($product['sku'], ['999', '1000', '1111'])) {
			for ($i = count($props) - 1; $i >= 0; $i--) {
				if ($props[$i]['name'] != 'выебри карточку') continue;
				unset($props[$i]);
			}
		}
		//артикул
		$props[] = [
			'name' => 'артикул',
			'value' => $product['sku']
		];
		//витрина
		if ($product['isVitrina']) {
			$props[] = [
				'name' => 'готов',
				'value' => 'на витрине'
			];
		}
		//цена
		$props[] = [
			'name' => 'цена',
			'value' => $product['price']
		];

		return $props;
	}
}
