<?

namespace php2steblya;

use php2steblya\Logger;
use LireinCore\YMLParser\YML as YMLparser;

class YML
{
	public static function ymlToArray(string $url)
	{
		$logger = Logger::getInstance();
		$catalog = [];
		try {
			$ymlParser = new YMLparser();
			$ymlParser->parse($url);
			$catalog['date'] = $ymlParser->getDate();
			$shop = $ymlParser->getShop();
			if ($shop->isValid()) {
				$catalog['offersCount'] = $shop->getOffersCount();
				$catalog['shopData'] = $shop->getData();
				$catalog['offers'] = [];
				foreach ($ymlParser->getOffers() as $offer) {
					if ($offer->isValid()) {
						$catalog['offers'][] = $offer->getData();
					} else {
						$logger->addToLog('error_offer', $offer->getErrors());
						throw new \Exception('offer not valid');
					}
				}
			} else {
				$logger->addToLog('error_shop', $shop->getErrors());
				throw new \Exception('shop not valid');
			}
		} catch (\Exception $e) {
			$logger->addToLog('error_message', $e->getMessage());
			$logger->addToLog('error_file', Logger::shortenPath(__FILE__));
			$logger->sendToAdmin();
		}
		return $catalog;
	}

	public static function arrayToYml(array $catalog)
	{
		$out = [];
		$out[] = '<?xml version="1.0" encoding="UTF-8"?>';
		$out[] = '<yml_catalog date="' . $catalog['date'] . '">';
		$out[] = '<shop>';
		$out[] = '<name>' . $catalog['shopData']['name'] . '</name>';
		$out[] = '<company>' . $catalog['shopData']['company'] . '</company>';
		$out[] = '<platform>' . $catalog['shopData']['platform'] . '</platform>';
		$out[] = '<version>' . $catalog['shopData']['version'] . '</version>';
		$out[] = '<currencies>';
		foreach ($catalog['shopData']['currencies'] as $currency) {
			$out[] = '<currency id="' . $currency['id'] . '" rate="' . $currency['rate'] . '"/>';
		}
		$out[] = '</currencies>';
		//$out[] = '<categories></categories>';
		$out[] = '<offers>';
		foreach ($catalog['offers'] as $offer) {
			preg_match('/tproduct\/(?:\d+-)?(\d+)/', $offer['url'], $groupId);
			$out[] = '<offer id="' . $offer['id'] . '" productId="' . $groupId[1] . '" quantity="9999">'; //quantity не работает, так как в срм ручное управление остатками. меняем через api
			$out[] = '<name>' . $offer['name'] . '</name>';
			$out[] = '<vendorCode>' . $offer['vendorCode'] . '</vendorCode>';
			//$out[] = '<description></description>';
			$out[] = '<picture>' . $offer['pictures'][0] . '</picture>';
			$out[] = '<url>' . $offer['url'] . '</url>';
			$out[] = '<price>' . $offer['price'] . '</price>';
			$out[] = '<currencyId>' . $offer['currencyId'] . '</currencyId>';
			$out[] = '<categoryId></categoryId>';
			/*foreach ($offer['params'] as $name => $value) {
			$out[] = '<param name="' . $name . '">' . $value . '</param>';
			}*/
			$out[] = '</offer>';
		}
		$out[] = '</offers>';
		$out[] = '</shop>';
		$out[] = '</yml_catalog>';
		return implode("\r\n", $out);
	}
}
