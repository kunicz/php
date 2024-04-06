<?

namespace php2steblya\order;

use php2steblya\order\OrderData_item;

class OrderData_product
{
	public static function prepare($product)
	{
		$product['price'] = (int) $product['price'];
		$product['amount'] = (int) $product['amount'];
		$product['quantity'] = (int) $product['quantity'];
		$product = self::isVitrina($product);
		$product['article'] = self::article($product);
		$product['isDonat'] = $product['sku'] == '1111';
		$product['isDopnik'] = str_starts_with($product['sku'], '888');
		$product['isPodpiska'] = str_starts_with($product['sku'], '666');
		$product = self::optionVyebriKartochku($product);
		$product = self::optionFormat($product);
		return $product;
	}

	private static function article($product)
	{
		$articleArray = explode('-', $product['sku']);
		if (count($articleArray) < 2) return $product['sku'];
		if (in_array($articleArray[0], explode(',', $_ENV['reserved_articles']))) return $product['sku'];
		return $articleArray[0];
	}

	private static function isVitrina($product)
	{
		$product['isVitrina'] = false;
		if (str_starts_with($product['sku'], '777')) {
			$product['isVitrina'] = true;
		}
		if (substr($product['sku'], -1) == 'v') {
			$product['isVitrina'] = true;
			$product['sku'] = substr($product['sku'], 0, -1);
		}
		return $product;
	}

	private static function optionVyebriKartochku($product)
	{
		for ($i = 0; $i < count($product['options']); $i++) {
			if ($product['options'][$i]['option'] != 'карточка') continue;
			$product['options'][$i]['option'] = 'выебри карточку';
			break;
		}
		return $product;
	}

	private static function optionFormat($product)
	{
		for ($i = 0; $i < count($product['options']); $i++) {
			if ($product['options'][$i]['option'] != 'формат') continue;
			$product['options'][$i]['option'] = 'фор мат';
			break;
		}
		return $product;
	}

	public static function getItemsForCrm($product)
	{
		return [
			'offer' => OrderData_item::getOffer($product),
			'properties' => OrderData_item::getProperties($product),
			'productName' => $product['name'],
			'quantity' => $product['quantity'],
			'initialPrice' => $product['price'],
			'purchasePrice' => 0
		];
	}

	public static function getVyebriKartochku($products)
	{
		$cardsItems = [];
		foreach ($products as $product) {
			foreach ($product['options'] as $option) {
				if ($option['option'] != 'выебри карточку') continue;
				$cardsItems[] = $option['variant'];
				break;
			}
		}
		return implode(', ', $cardsItems);
	}

	public static function getSummary($products)
	{
		$summaryItems = [];
		foreach ($products as $product) {
			$summaryItem = '';
			//название
			$summaryItem .= $product['name'];
			//формат
			foreach ($product['options'] as $option) {
				if ($option['option'] != 'фор мат') continue;
				$summaryItem .= ' - ' . $option['variant'];
				break;
			}
			//свойства
			$dops = [];
			foreach ($product['options'] as $option) {
				if (in_array($option['option'], ['выебри карточку', 'фор мат'])) continue;
				$dops[] = $option['option'] . ': ' . $option['variant'];
			}
			if (!empty($dops)) $summaryItem .= ' (' . implode(', ', $dops) . ')';
			//количество
			$summaryItem .= ' (' . $product['quantity'] . ' шт)';

			$summaryItems[] = $summaryItem;
		}
		return implode(', ', $summaryItems);
	}
}
