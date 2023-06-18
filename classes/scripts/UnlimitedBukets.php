<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\ApiRetailCrmResponse_products_get as Products_get;
use php2steblya\ApiRetailCrmResponse_inventories_upload as Inventories_upload;

class UnlimitedBukets
{
	public $log;
	private $source;
	private $products;

	public function init(): void
	{
		$this->source = 'products with zero inventories';
		$this->log = new Logger($this->source);
		$this->collectZeroProducts();
		if (!$this->products->has()) return;
		$this->unlimiteProducts();
		$this->log->setRemark(implode(',', $this->products->getNames()));
		$this->log->writeSummary();
	}
	/**
	 * получаем все товары с нулевыми остатками из api 
	 */
	private function collectZeroProducts()
	{
		$args = [
			'limit' => 100,
			'filter' => [
				'maxQuantity' => 0
			]
		];
		foreach (allowed_sites() as $site) {
			$args['filter']['sites'][] = $site;
		}
		$this->products = new Products_get($this->source, $args);
		$this->log->push('1. collect products', $this->products->getLog());
	}
	/**
	 * заполняем остатки
	 */
	private function unlimiteProducts()
	{
		$names = [];
		$stores = [
			[
				'code' => 'rai-tsvetov',
				'available' => 99999
			]
		];
		$offers = [];
		foreach ($this->products->get() as $product) {
			$names[] = $product->name;
			foreach ($product->offers as $offer) {
				$offers[] = [
					'id' => $offer->id,
					'stores' => $stores
				];
				if (count($offers) == 250) break 2; //нельзя больше 250 офферов за раз
			}
		}
		$args = [
			'offers' => json_encode($offers)
		];
		$invetories = new Inventories_upload($this->source, $args);
		$this->log->push('2. update products', $invetories->getLog());
	}
}
