<?php

namespace php2steblya\order\retailcrm\splitter_strategies;

use php2steblya\order\retailcrm\splitter_strategies\Abstract_strategy as SplitterStrategy;

class Podpiska extends SplitterStrategy
{
	public function split(): void
	{
		$this->podpiska();
		$this->dopnik();
	}

	public function needToSplit(): bool
	{
		foreach ($this->products as $product) {
			if (!$product['isPodpiska']) continue;
			return true;
		}
		return false;
	}

	// 1выделаем товар(ы) - подписку в отедельный заказ для CRM
	private function podpiska(): void
	{
		foreach ($this->products as $key => $product) {
			if (!$product['isPodpiska']) continue;

			$dostavkiFrequency = $this->dostavkiFrequency($product);
			$dostavkiCount = $this->dostavkiCount($product);

			for ($i = 0; $i < $dostavkiCount; $i++) {
				$this->resetOd();
				$this->addProductToOd($this->podpiskaProduct($product, $i, $dostavkiCount, $this->od['dostavka_price']));
				// вносим изменения в заказы, начиная со второго
				if ($i) {
					$this->od['lovixlube'] = false;
					$this->od['text_v_kartochku'] = '';
					$this->od['payment']['delivery_price'] = 0;
					$this->od['dostavka_date'] = $this->dostavkaDate($i, $dostavkiFrequency);
				}
				$this->addOdToCrmOds();
			}
			$this->removeProductByKey($key);
		}
	}

	// если в заказе есть помимо подписки еще и допник, то добавляем его в первую доставку
	private function dopnik(): void
	{
		foreach ($this->products as $key => $product) {
			if (!$product['isDopnik']) continue;

			$this->appendProductToFirstCrmOd($product);
			$this->removeProductByKey($key);
		}
	}

	// количество доставок в подписке
	private function dostavkiCount(array $product): int
	{
		$sku = $product['sku'];
		$position_of_x = strpos($sku, 'x');
		if ($position_of_x !== false) {
			$number_after_x = substr($sku, $position_of_x + 1);
			return intval($number_after_x);
		} else {
			return 1;
		}
	}

	// частота доставок в подписке
	private function dostavkiFrequency(array $product): int
	{
		if (empty($product['options'])) return 1;

		foreach ($product['options'] as $option) {
			if ($option['option'] == 'как часто') {
				switch ($option['variant']) {
					case 'раз в неделю':
						return 1;
					case 'раз в две недели':
						return 2;
				}
			}
		}

		return 1;
	}

	// дата доставки
	// $dostavkaIndex - номер доставки
	// $dostavkiFrequency - частота доставок в подписке
	// получаем дату доставки через преобразование даты в timestamp
	// и добавление к ней количества дней, равного номеру доставки умноженному на частоту доставок
	private function dostavkaDate(int $dostavkaIndex, int $dostavkiFrequency)
	{
		$date = strtotime($this->od['dostavka_date'] . ' +' . ($dostavkaIndex * 7 * $dostavkiFrequency) . ' days');
		return date('Y-m-d', $date);
	}

	// формируем продукт подписки для CRM
	// $dostavkaIndex - номер доставки
	// $dostavkiCount - количество доставок в подписке
	// $price - цена товара
	private function podpiskaProduct(array $product, int $dostavkaIndex, int $dostavkiCount, int $dostavkaPrice)
	{
		$product['price'] = round($product['price'] / $dostavkiCount - $dostavkaPrice);
		$product['quantity'] = 1;
		$product['amount'] = $product['price'];
		$product['options'][] = ['option' => 'доставка', 'variant' => '№' . ($dostavkaIndex + 1)];
		return $product;
	}
}
