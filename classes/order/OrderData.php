<?

namespace php2steblya\order;

use php2steblya\utils\Input;
use php2steblya\order\OrderData_name;
use php2steblya\order\OrderData_adres;
use php2steblya\order\OrderData_product;
use php2steblya\order\OrderData_telegram;
use php2steblya\order\OrderData_dostavka;

class OrderData
{
	public static function prepare($od)
	{
		$od = self::underscoreKeys($od);
		$od = self::booleanYes($od);
		$od = self::sanitizeInputs($od);

		//zakazchik
		list($od['name_zakazchika_firstName'], $od['name_zakazchika_lastName'], $od['name_zakazchika_patronymic']) = OrderData_name::explode($od['name_zakazchika']);
		$od['messenger_zakazchika'] = OrderData_telegram::get($od['messenger_zakazchika']);
		$od['zakazchik_is_poluchatel'] = $od['phone_zakazchika'] == $od['phone_poluchatelya'];
		//dostavka
		$od['dostavka_price'] = (int) $od['dostavka_price'];
		$od['dostavka_interval'] = OrderData_dostavka::getInterval($od['dostavka_interval']);
		//paid
		$od['paid'] = isset($od['payment']['systranid']);
		//products
		$od['payment']['amount'] = (int) $od['payment']['amount'];
		$od['payment']['subtotal'] = (int) $od['payment']['subtotal'];
		$od['payment']['delivery_price'] = (int) $od['payment']['delivery_price'];
		foreach ($od['payment']['products'] as $key => $product) {
			$product['shop_crm_id'] = $od['shop_crm_id'];
			$od['payment']['products'][$key] = OrderData_product::prepare($product);
		}

		return $od;
	}

	public static function getOrdersWithSeparatedProducts($od)
	{
		$ods = [];
		$products = self::sortProducts($od['payment']['products']);

		//обычный
		foreach ($products as $product) {
			if ($product['isDopnik'] || $product['isPodpiska'] || $product['isDonat']) continue;
			$odNew = array_merge([], $od);
			$odNew['payment']['products'] = [$product];
			$ods[] = $odNew;
		}
		//подписка
		foreach ($products as $product) {
			if (!$product['isPodpiska']) continue;
			$dostavkiFrequency = 1;
			$dostavkiCount = self::podpiskaDostavkiCount($product['sku']);
			$price = $product['price'];
			foreach ($product['options'] as $option) {
				/*if ($option['option'] == 'скока доставок') {
					$dostavkiCount = intval(preg_replace("/[a-zA-Z\s]/", "", $option['variant']));
				}*/
				if ($option['option'] == 'как часто') {
					switch ($option['variant']) {
						case 'раз в неделю':
							$dostavkiFrequency = 1;
							break;
						case 'раз в две недели':
							$dostavkiFrequency = 2;
							break;
					}
				}
			}
			for ($i = 0; $i < $dostavkiCount; $i++) {
				$odNew = array_merge([], $od);
				$productNew = array_merge([], $product);
				$productNew['price'] = round($price / $dostavkiCount);
				$productNew['amount'] = $productNew['price'];
				$productNew['options'][] = ['option' => 'доставка', 'variant' => '№' . ($i + 1)];
				if ($i) {
					$odNew['lovixlube'] = false;
					$odNew['text_v_kartochku'] = '';
					$odNew['payment']['delivery_price'] = 0;
					$odNew['dostavka_date'] = date('Y-m-d', strtotime($odNew['dostavka_date'] . ' +' . ($i * 7 * $dostavkiFrequency) . ' days'));
				}
				$odNew['payment']['products'] = [$productNew];

				$ods[] = $odNew;
			}
		}
		//допник
		//допники добавляем к первому букету или в отдельный заказ (если букетов нет)
		foreach ($products as $product) {
			if (!$product['isDopnik']) continue;
			if (count($ods)) {
				$ods[0]['payment']['products'][] = $product;
			} else {
				$odNew = array_merge([], $od);
				$odNew['payment']['products'] = [$product];
				$ods[] = $odNew;
			}
		}
		//донат
		foreach ($products as $product) {
			if (!$product['isDonat']) continue;

			$odNew = array_merge([], $od);
			$odNew['onanim'] = false;
			$odNew['lovixlube'] = false;
			$odNew['dostavka_price'] = 0;
			$odNew['comment_courier'] = '';
			$odNew['text_v_kartochku'] = '';
			$odNew['dostavka_interval'] = '';
			$odNew['name_poluchatelya'] = '';
			$odNew['phone_poluchatelya'] = '';
			$odNew['adres_poluchatelya_dom'] = '';
			$odNew['adres_poluchatelya_city'] = '';
			$odNew['adres_poluchatelya_etazh'] = '';
			$odNew['adres_poluchatelya_region'] = '';
			$odNew['adres_poluchatelya_street'] = '';
			$odNew['adres_poluchatelya_korpus'] = '';
			$odNew['adres_poluchatelya_podezd'] = '';
			$odNew['adres_poluchatelya_domofon'] = '';
			$odNew['adres_poluchatelya_stroenie'] = '';
			$odNew['adres_poluchatelya_kvartira'] = '';
			$odNew['uznat_adres_u_poluchatelya'] = false;
			$odNew['payment']['products'] = [$product];
			$odNew['payment']['products'][0]['options'] = [];
			$odNew['payment']['delivery_price'] = 0;

			$ods[] = $odNew;
		}
		//пересчет payment amount и payment subtotal
		foreach ($ods as $i => $od) {
			$subtotal = 0;
			foreach ($od['payment']['products'] as $product) {
				$subtotal += $product['amount'];
			}
			$ods[$i]['payment']['subtotal'] = $subtotal;
			$ods[$i]['payment']['amount'] = $subtotal + $ods[$i]['payment']['delivery_price'];
		}

		return $ods;
	}

	private static function sortProducts($products)
	{
		if (count($products) == 1) return $products;
		$flowersProducts = [];
		for ($i = count($products) - 1; $i >= 0; $i--) {
			if ($products[$i]['isDopnik'] || $products[$i]['isDonat']) continue;
			$flowersProducts[] = $products[$i];
			unset($products[$i]);
		}
		return array_merge($flowersProducts, $products);
	}

	public static function getCrmArgs($od)
	{
		$order = [
			'externalId'			=> 'php_' . time() . uniqid(),
			'orderMethod'			=> 'php',
			'status'				=> 'new',
			'site'					=> $od['site'],
			'firstName'				=> $od['name_zakazchika_firstName'],
			'lastName'				=> $od['name_zakazchika_lastName'],
			'patronymic'			=> $od['name_zakazchika_patronymic'],
			'phone'					=> $od['phone_zakazchika'],
			'customerComment'		=> $od['comment_courier'],
			'managerComment'		=> $od['comment_florist'],
			'delivery' => [
				'code'				=> 'courier',
				'address' => [
					'text'			=> OrderData_adres::getText($od)
				],
				'date'				=> $od['dostavka_date'],
				'time' => [
					'custom'		=> $od['dostavka_interval']
				],
				'cost'				=> $od['dostavka_price'] + $od['payment']['delivery_price'],
				'netCost'			=> 0
			],
			'payments' => [
				[
					'type'				=> 'site',
					'paidAt'			=> $od['date'],
					'amount'			=> $od['payment']['amount'],
					'status'			=> $od['paid'] ? 'paid' : 'not-paid',
					'externalId'		=> $od['payment']['systranid'] . '-' . uniqid(),
					'comment'			=> self::paymentComment()
				]
			],
			'customFields' => [
				'onanim'						=> $od['onanim'],
				'lovixlube'						=> $od['lovixlube'],
				'ya_client_id_order'			=> $od['ya_client_id'],
				'stoimost_dostavki_iz_tildy'	=> $od['dostavka_price'],
				'text_v_kartochku'				=> $od['text_v_kartochku'],
				'name_poluchatelya'				=> $od['name_poluchatelya'],
				'phone_poluchatelya'			=> $od['phone_poluchatelya'],
				'otkuda_o_nas_uznal'			=> $od['otkuda_uznal_o_nas'],
				'messenger_zakazchika'			=> $od['messenger_zakazchika'],
				'zakazchil_poluchatel'			=> $od['zakazchik_is_poluchatel'], //опечатка в названии поля в crm бесит
				'uznat_adres_u_poluchatelya'	=> $od['uznat_adres_u_poluchatelya'],
				'adres_poluchatelya'			=> OrderData_adres::getText($od),
				'bukety_v_zakaze'				=> OrderData_product::getSummary($od['payment']['products']),
				'auto_courier'					=> OrderData_dostavka::isVehicleOnly($od['payment']['products']),
				'card'							=> OrderData_product::getVyebriKartochku($od['payment']['products']),
			],
			'source' => [
				'keyword' 			=> $od['utm_term'],
				'source'			=> $od['utm_source'],
				'medium'			=> $od['utm_medium'],
				'content'			=> $od['utm_content'],
				'campaign'			=> $od['utm_campaign']
			],
			'costumer' => [
				'id'				=> $od['customer_crm_id']
			],
			'items'	=> []
		];
		foreach ($od['payment']['products'] as $product) {
			$order['items'][] = OrderData_product::getItemsForCrm($product);
		}
		//если донат
		if ($od['payment']['products'][0]['isDonat']) {
			$order['status'] = 'complete';
			$order['customFields']['florist'] = 'boss';
		}

		return $order;
	}

	private static function podpiskaDostavkiCount($sku)
	{
		$position_of_x = strpos($sku, 'x');
		if ($position_of_x !== false) {
			$number_after_x = substr($sku, $position_of_x + 1);
			return intval($number_after_x);
		} else {
			return 1;
		}
	}

	private static function underscoreKeys($od)
	{
		foreach ($od as $key => $value) {
			unset($od[$key]);
			$od[str_replace("-", "_", $key)] = $value;
		}
		return $od;
	}

	private static function booleanYes($od)
	{
		foreach ($od as $key => $value) {
			if ($value == 'yes') $od[$key] = true;
		}
		return $od;
	}

	private static function paymentComment()
	{
		if (isset($payment['promocode'])) {
			return 'применен промокод: "' . $payment['promocode'] . '" (' . $payment['discount'] . ' р.)';
		}
		return '';
	}

	private static function sanitizeInputs($od)
	{
		$fieldsToSanitize = [
			'name_zakazchika',
			'name_poluchatelya',
			'messenger_zakazchika',
			'comment_courier',
			'comment_florist',
			'text_v_kartochku',
			'adres_poluchatelya_city',
			'adres_poluchatelya_street',
			'adres_poluchatelya_dom',
			'adres_poluchatelya_korpus',
			'adres_poluchatelya_stroenie',
			'adres_poluchatelya_kvartira',
			'adres_poluchatelya_etazh',
			'adres_poluchatelya_podezd',
			'adres_poluchatelya_domofon'
		];
		foreach ($od as $key => $value) {
			if (!in_array($key, $fieldsToSanitize)) continue;
			$od[$key] = Input::sanitize($value);
		}
		return $od;
	}
}
