<?php

namespace php2steblya\scripts\tilda;

use php2steblya\YML;
use php2steblya\File;
use php2steblya\Script;
use php2steblya\db\DbTable;

// парсинг каталогов тильды
class YmlCatalog extends Script
{
	private ?string $urlTilda; // URL для получения данных из Tilda.
	private ?string $urlLocal; // локальный URL для сохранения каталога.
	private array $catalog; // массив данных каталога.
	private array $shop; // магазин
	private DbTable $dbCatalog; // объект для работы с базой данных.

	public function init(): void
	{
		$this->dbCatalog = $this->db->tilda_yml_catalog();
		foreach ($this->shops as $shop) {
			try {
				$this->shop = $shop;
				$this->logger->setGroup($this->shop['shop_crm_code']);

				if (!$this->processUrls()) continue;
				if (!$this->processHashes()) continue;

				$this->processCatalog();
			} catch (\Exception $e) {
				$this->logger->addError($e);
			}
		}
	}

	// обработка URL-ов каталога
	private function processUrls(): bool
	{
		$this->logger->setSubGroup('urls');

		$this->urlLocal = $this->getUrlLocal();
		$this->urlTilda = $this->getUrlTilda();

		$this->logger
			->add('tilda_url', $this->urlTilda)
			->add('local_url', $this->urlLocal)
			->exitSubGroup();

		return $this->urlTilda && $this->urlLocal;
	}

	// обработка хэшей каталога
	private function processHashes(): bool
	{
		$this->logger->setSubGroup('hashes');

		$oldHash = $this->getOldHash();
		$newHash = $this->getNewHash();
		$changed = $oldHash !== $newHash;

		$this->logger
			->add('old', $oldHash)
			->add('new', $newHash)
			->add('changed', $changed)
			->exitSubGroup();

		return $changed && $oldHash && $newHash;
	}

	// обработка каталога
	private function processCatalog(): void
	{
		$this->logger->setSubGroup('catalogs');
		$this->catalog = YML::ymlToArray($this->urlTilda);

		$this->preserveDisabledOffers();
		$this->optimizeOffers();
		$this->updateCatalog();

		$this->logger->exitSubGroup();
	}

	// сохраняет отключенные предложения.	
	private function preserveDisabledOffers(): void
	{
		$this->logger->setSubGroup('catalog_db');
		$args = [
			'fields' => ['catalog'],
			'where' => ['shop_crm_id' => $this->shop['shop_crm_id']],
			'limit' => 1
		];
		$catalogOld = json_decode($this->dbCatalog->get($args), true);
		$this->logger
			->exitSubGroup()
			->add('catalog_from_tilda', $this->catalog)
			->add('catalog_from_db', $catalogOld);

		$offersToPreserve = [];
		if (isset($catalogOld['offers'])) {
			foreach ($catalogOld['offers'] as $offer) {
				if (in_array($offer['id'], $this->getOffersIds())) continue;
				$this->catalog['offers'][] = $offer;
				$offersToPreserve[] = $offer['name'];
			}
		}
		$this->catalog['offersCount'] = count($this->catalog['offers']);

		$this->logger
			->add('offers_preserved', $offersToPreserve)
			->add('catalog_with_preserved_offers', $this->catalog);
	}

	// оптимизация предложений в каталоге.
	// этот метод выполняет несколько операций по оптимизации данных в каталоге:
	// 1. убирает неоригинальные витринные букеты и товары с дополнительной наценкой.
	// 2. удаляет товары с дубликатами ID.
	// 3. корректирует названия товаров, заменяя амперсанды и суффиксы в именах.
	private function optimizeOffers(): void
	{
		$offersToRemove = [];
		$offersIdsLocal = [];

		foreach ($this->catalog['offers'] as &$offer) {
			$this->processOfferWithNacenka($offer);

			if ($this->shouldRemoveOffer($offer)) {
				$offersToRemove[] = [$offer['id'], $offer['name']];
				continue;
			}

			if ($this->isDuplicateOffer($offer, $offersIdsLocal)) {
				$offersToRemove[] = [$offer['id'], $offer['name']];
			}

			$this->processLoveIS($offer);
			$this->replaceAmpersand($offer);
			$this->removeFormatFromVitrinaSpecial($offer);
		}

		$offersRemovedNames = $this->removeOffers($offersToRemove);
		$this->catalog['offers'] = array_values($this->catalog['offers']);
		$this->catalog['offersCount'] = count($this->catalog['offers']);

		$this->logger
			->add('catalog_final', $this->catalog)
			->add('offers_removed', $offersRemovedNames);
	}

	// обрабатывает товары с наценкой.
	private function processOfferWithNacenka(array &$offer): void
	{
		if (preg_match('/(\d+)v1$/', $offer['id'], $offerIdTrimmed)) {
			$offer['name'] = preg_replace('/\s\-\s.*$/', '', $offer['name']);  //отрезаем суффикс из имени товара
			$offer['id'] = $offerIdTrimmed[1]; // меняем id на нормальный (без "v1")
		}
	}

	// проверяет, нужно ли удалять предложение по условиям витринных товаров или товаров с наценкой.
	private function shouldRemoveOffer(array $offer): bool
	{
		$conditions = [
			substr($offer['vendorCode'], -1) == 'v', // витринный вариант
			preg_match('/v\d+$/', $offer['id']) // товар с дополнительной наценкой
		];

		foreach ($conditions as $condition) {
			if ($condition) return true;
		}
		return false;
	}

	// проверяет, является ли предложение дубликатом.
	private function isDuplicateOffer(array $offer, array &$offersIdsLocal): bool
	{
		if (!in_array($offer['id'], $offersIdsLocal)) {
			$offersIdsLocal[] = $offer['id'];
			return false;
		}
		return true;
	}

	// обрабатывает название предложения для "Love IS" и корректирует его.
	private function processLoveIS(array &$offer): void
	{
		if (preg_match('/^ЛЮБЛЮЮЮ\.\.\./', $offer['name'])) {
			$offer['name'] = 'ЛЮБЛЮЮЮ' . $offer['description'];
		}
	}

	// заменяет амперсанд (&) на "и" в названии предложения.
	private function replaceAmpersand(array &$offer): void
	{
		$offer['name'] = str_replace('&', ' и ', $offer['name']);
	}

	// убирает формат из названия товара с кодом "777-" в vendorCode (витринный эксклюзив).
	private function removeFormatFromVitrinaSpecial(array &$offer): void
	{
		if (preg_match('/^777\-/', $offer['vendorCode'])) {
			$offer['name'] = preg_replace('/\s\-\s.*$/', '', $offer['name']);
		}
	}

	// удаляет предложения из каталога и возвращает их имена для логирования.
	private function removeOffers(array $offersToRemove): array
	{
		$offersRemovedNames = [];

		foreach ($offersToRemove as $offerToRemove) {
			$offersRemovedNames[] = $offerToRemove[1];
			unset($this->catalog['offers'][$offerToRemove[0]]);
		}

		return $offersRemovedNames;
	}

	// обновляет каталог.
	private function updateCatalog(): void
	{
		$this->logger->setSubGroup('update');

		$yml = YML::arrayToYml($this->catalog);
		$file = new File($this->urlLocal);
		$file->write($yml);

		$args = [
			'set' => [
				'hash' => $this->getNewHash(),
				'catalog' => json_encode($this->catalog)
			],
			'where' => [
				'shop_crm_id' => $this->shop['shop_crm_id']
			]
		];
		$this->dbCatalog->update($args);

		$this->logger->exitSubGroup();
	}

	// получает URL для Tilda.
	private function getUrlTilda(): ?string
	{
		$this->logger->setSubGroup('db');

		$args = [
			'fields' => ['url'],
			'where' => ['shop_crm_id' => $this->shop['shop_crm_id']],
			'limit' => 1
		];
		$url = $this->dbCatalog->get($args);
		if (empty($url)) $url = '';

		$this->logger->exitSubGroup();
		return $url;
	}

	// получает локальный URL для сохранения каталога.
	private function getUrlLocal(): ?string
	{
		return implode('/', [dirname(__FILE__, 3), 'yml_catalogs', $this->shop['shop_crm_code'] . '.yml']);
	}

	// получает ID предложений из каталога.
	private function getOffersIds(): array
	{
		$offersIds = [];
		foreach ($this->catalog['offers'] as $offer) {
			$offersIds[] = $offer['id'];
		}
		return $offersIds;
	}

	// получает новый хеш каталога.
	private function getNewHash(): ?string
	{
		if (!$this->urlTilda) return '';
		return hash('md5', file_get_contents($this->urlTilda));
	}

	// получает старый хеш каталога.
	private function getOldHash(): ?string
	{
		$this->logger->setSubGroup('db');

		$args = [
			'fields' => ['hash'],
			'where' => ['shop_crm_id' => $this->shop['shop_crm_id']],
			'limit' => 1
		];
		$hash = $this->dbCatalog->get($args);
		if (empty($hash)) $hash = '';

		$this->logger->exitSubGroup();
		return $hash;
	}
}
