<?
require __DIR__ . '/inc/headers-cors.php';
require __DIR__ . '/inc/functions-api.php';

use LireinCore\YMLParser\YML;

$yml = new YML();
try {
	$yml->parse('https://2steblya.ru/tstore/yml/81f7b18311537d636b0044cd46380d01.yml');
	$date = $yml->getDate();
	$shop = $yml->getShop();
	if ($shop->isValid()) {
		$offersCount = $shop->getOffersCount();
		$shopData = $shop->getData();
		foreach ($yml->getOffers() as $offer) {
			if ($offer->isValid()) {
				$offerCategoryHierarchy = $shop->getCategoryHierarchy($offer->getCategoryId());
				$offerData = $offer->getData();
				echo json_encode($offerData);
				die();
			} else {
				var_dump($offer->getErrors());
			}
		}
		//echo json_encode($shopData);
	} else {
		var_dump($shop->getErrors());
	}
} catch (\Exception $e) {
	echo $e->getMessage();
}
die();
