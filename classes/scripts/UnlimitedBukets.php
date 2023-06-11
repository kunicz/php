<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\ApiRetailCrm as api;

class UnlimitedBukets
{
	public $log;
	private $response;
	private $products;

	public function init(): void
	{
		$this->log = new Logger('products with zero inventories');
		$this->collectZeroProducts();
		if (!$this->response->getCount()) return;
		$this->unlimiteProducts();
	}
	/**
	 * получаем все товары с нулевыми остатками из api 
	 * 
	 * @return void Returns data of type void
	 */
	private function collectZeroProducts(): void
	{
		try {
			$args = [
				'limit' => 100,
				'filter' => [
					'maxQuantity' => 0
				]
			];
			foreach (allowed_sites() as $site) {
				$args['filter']['sites'][] = str_replace(' ', '', strtolower($site)); //Stay True flower => staytrueflowers
			}
			$api = new api();
			$api->get('store/products', $args);
			$this->log->push('queryString', $args, 0);
			$this->log->push('response', $api->response, 0);
			if ($api->hasErrors()) {
				throw new \Exception($api->getError());
			}
			if (!$api->getCount()) {
				$this->log->pushError('no products found');
			}
			$this->response = $api;
			$this->products = $api->response->products;
		} catch (\Exception $e) {
			$this->log->pushError($e->getMessage());
			$this->log->writeSummary();
			die($this->log->getJson());
		}
	}
	/**
	 * заполняем остатки
	 * 
	 * @return void Returns data of type void
	 */
	private function unlimiteProducts(): void
	{
		try {
			$names = [];
			$stores = [
				[
					'code' => 'rai-tsvetov',
					'available' => 99999
				]
			];
			$offers = [];
			foreach ($this->products as $product) {
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
			$api = new api();
			$api->post('store/inventories/upload', $args);
			$this->log->push('offers', $offers, 1);
			$this->log->push('queryString', $args, 1);
			$this->log->push('response', $api->response, 1);
			if ($api->hasErrors()) {
				throw new \Exception($api->getError());
			}
			$this->log->setRemark($names);
			$this->log->writeSummary();
		} catch (\Exception $e) {
			$this->log->pushError($e->getMessage());
			$this->log->writeSummary();
			die($this->log->getJson());
		}
	}
}
