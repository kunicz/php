<?php

namespace php2steblya\order\retailcrm\splitter_strategies;

use php2steblya\order\retailcrm\splitter_strategies\Abstract_strategy as SplitterStrategy;

class Donat extends SplitterStrategy
{
	public function split(): void
	{
		foreach ($this->products as $key => $product) {
			if (!$product['isDonat']) continue;

			$product['options'] = [];

			$this->resetOd();
			$this->od['onanim'] = false;
			$this->od['lovixlube'] = false;
			$this->od['initial_dostavka_price'] = 0;
			$this->od['additional_dostavka_price'] = 0;
			$this->od['courier_dostavka_price'] = 0;
			$this->od['payment']['delivery_price'] = 0;
			$this->od['comment_courier'] = '';
			$this->od['text_v_kartochku'] = '';
			$this->od['dostavka_interval'] = '';
			$this->od['name_poluchatelya'] = '';
			$this->od['phone_poluchatelya'] = '';
			$this->od['adres_poluchatelya_dom'] = '';
			$this->od['adres_poluchatelya_city'] = '';
			$this->od['adres_poluchatelya_etazh'] = '';
			$this->od['adres_poluchatelya_region'] = '';
			$this->od['adres_poluchatelya_street'] = '';
			$this->od['adres_poluchatelya_korpus'] = '';
			$this->od['adres_poluchatelya_podezd'] = '';
			$this->od['adres_poluchatelya_stroenie'] = '';
			$this->od['adres_poluchatelya_kvartira'] = '';
			$this->od['adres_poluchatelya_domofon'] = '';
			$this->od['uznat_adres_u_poluchatelya'] = false;
			$this->addProductToOd($product);
			$this->addOdToCrmOds();
			$this->removeProductByKey($key);
		}
	}

	public function needToSplit(): bool
	{
		foreach ($this->products as $product) {
			if (!$product['isDonat']) continue;
			return true;
		}
		return false;
	}
}
