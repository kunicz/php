<?

namespace php2steblya\scripts;

use php2steblya\Logger;
use php2steblya\ApiRetailCrm as Api;
use php2steblya\LoggerException as Exception;

class UnlimitedBukets
{
	public $log;
	private $response;
	private $products;

	public function init(): void
	{
		$this->log = new Logger('products with zero inventories');
		$this->collectZeroProducts();
		if (!$this->response->getCount()) {
			$this->log->pushNote('no products found');
			return;
		}
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
				$args['filter']['sites'][] = $site;
			}
			$api = new Api();
			$api->get('store/products', $args);
			$this->log->insert('collect products');
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
			$this->response = $api;
			$this->products = $api->response->products;
		} catch (Exception $e) {
			$e->abort($this->log);
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
			$api = new Api();
			$api->post('store/inventories/upload', $args);
			$this->log->insert('unlimite inventories');
			$this->log->push('offers', $offers);
			$this->log->push('queryString', $args);
			$this->log->push('response', $api->response);
			if ($api->hasErrors()) {
				throw new Exception($api->getError());
			}
			$this->log->setRemark($names);
			$this->log->writeSummary();
		} catch (Exception $e) {
			$e->abort($this->log);
		}
	}
}
