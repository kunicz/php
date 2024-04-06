<?

namespace php2steblya\order;

class OrderData_dostavka
{
	public static function getInterval($string)
	{
		$pattern = '/^с \d{2}:\d{2} до \d{2}:\d{2}/';
		if (preg_match($pattern, $string, $matches)) {
			return $matches[0];
		} else {
			return $string;
		}
	}

	public static function isVehicleOnly($products)
	{
		$autoFormats = explode(',', $_ENV['vehicle_only_products']);
		foreach ($products as $product) {
			foreach ($product['options'] as $option) {
				if (in_array($option['option'], ['фор мат', 'Размер'])) continue;
				if (!in_array($option['variant'], $autoFormats)) break;
				return true;
			}
		}
		return false;
	}
}
