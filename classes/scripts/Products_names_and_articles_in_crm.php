<?

namespace php2steblya\scripts;

use php2steblya\DB;
use php2steblya\Logger;
use php2steblya\retailcrm\Response_store_products_get;
use php2steblya\retailcrm\Response_store_products_batch_edit_post;

class Products_names_and_articles_in_crm extends Script
{
	private $productsFromCrm;
	private $productsFromYml;
	private array $productsToEdit = [];

	public function __construct($scriptData = [])
	{
		$this->db = DB::getInstance();
		$this->logger = Logger::getInstance();
		$this->logger->addToLog('script', Logger::shortenPath(__FILE__));
		$this->site = isset($scriptData['site']) ? $scriptData['site'] : null;
	}

	public function init()
	{
		$sitesFromDB = $this->site ? $this->getSiteFromDB(['code' => $this->site]) : $this->getSitesFromDB();
		if (empty($sitesFromDB)) return;
		foreach ($sitesFromDB as $siteFromDB) {
			$this->site = $siteFromDB;
			$this->productsToEdit = [];

			$this->collectProducts(1);
			$this->editProducts();
		}

		echo json_encode($this->logger->getLogData());
	}

	private function collectProducts($page)
	{
		$response = new Response_store_products_get();
		$args = [
			'limit' => 100,
			'page' => $page,
			'filter' => [
				//'ids' => [2218],
				'sites' => [$this->site->shop_crm_code]
			]
		];
		$response->getProductsFromCRM($args);
		$this->productsFromCrm = $response->getProducts();
		$this->logger->addToLog($this->site->shop_crm_code . '_productsFromCrm_page' . $page, $this->productsFromCrm);
		$this->productsFromYml = $this->getProductsFromYml();
		foreach ($this->productsFromCrm as $product) {
			$this->checkProduct($product);
		}

		if ($response->getTotalPageCount() > $page) {
			$this->collectProducts($page + 1);
		}

		$this->logger->addToLog($this->site->shop_crm_code . '_productsToEdit', $this->productsToEdit);
		$this->logger->addToLog($this->site->shop_crm_code . '_productsFromYml', $this->productsFromYml);
	}

	private function checkProduct($productFromCrm)
	{
		/**
		 * 1. надо проверить артикул (артикул должен быть только номерной, кроме 777)
		 * 2. надо проверить название (если в названии есть приписка с " - букетик", то этот суффикс надо обрезать)
		 */
		foreach ($this->productsFromYml as $productFromYml) {
			$externalId = isset($productFromYml['groupId']) ? $productFromYml['groupId'] : $productFromYml['id'];
			if ($externalId != $productFromCrm->externalId) continue;
			$name = $this->checkName($productFromYml['name']);
			$article = $this->checkArticle($productFromYml['vendorCode']);
			if ((!$name || $name == $productFromCrm->name) && (!$article || $article == $productFromCrm->article)) return;
			$args = [
				'id' => $productFromCrm->id
			];
			if ($name) $args['name'] = $name;
			if ($article) $args['article'] = $article;
			$this->productsToEdit[] = $args;
			break;
		}
	}

	private function editProducts()
	{
		if (empty($this->productsToEdit)) return;
		$chunks = array_chunk($this->productsToEdit, 50); // разбиваем массив на массивы по 50, потому что store_products_batch_edit не принимает больше 50 товаров
		foreach ($chunks as $chunk) {
			$response = new Response_store_products_batch_edit_post();
			$response->editProductsInCRM(['products' => json_encode($chunk)]);
		}
	}

	private function getProductsFromYml()
	{
		$response = $this->db->sql("SELECT catalog FROM tilda_yml_catalog WHERE shop_crm_id = '{$this->site->shop_crm_id}'");
		if (!isset($response[0]->catalog)) return [];
		$catalog = json_decode($response[0]->catalog, true);
		return $catalog['offers'];
	}

	private function checkArticle($article)
	{
		$reserved = explode(',', $_ENV['reserved_articles']);
		$articleArray = explode('-', $article);
		if (count($articleArray) < 2) return null;
		if (in_array($articleArray[0], $reserved)) return null;
		return $articleArray[0];
	}

	private function checkName($name)
	{
		$pattern = '/\s-\s[^\p{Lu}]+[^-]*$/u';
		if (preg_match($pattern, $name)) {
			// If found, remove everything after the last " - "
			return preg_replace($pattern, '', $name);
		} else {
			// If not found, return the original string
			return null;
		}
	}
}
