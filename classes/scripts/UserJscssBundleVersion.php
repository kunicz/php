<?php

namespace php2steblya\scripts;

use php2steblya\Script;

// Класс для получения версии бандла длы user_jscss сборок
class UserJscssBundleVersion extends Script
{
	public function init()
	{
		if (empty($this->scriptData['bundle'])) throw new \Exception("не указано название бандла");

		$bundlePath = $this->getBundlePath();
		$version = $this->getBundleVersion($bundlePath);
		$this->setResponse($version);
	}

	// получаем путь до файла бандла
	private function getBundlePath(): string
	{
		$this->logger->add('bundle', $this->scriptData['bundle']);

		$user_jscss_folder = $_ENV['PROJECT_PATH'] . '/jscss/user_jscss';
		if (!is_dir($user_jscss_folder)) throw new \Exception("папка user_jscss не найдена");

		$bundlePath = $user_jscss_folder . '/' . $this->scriptData['bundle'] . '.js';
		if (!file_exists($bundlePath)) throw new \Exception('бандл ' . $this->scriptData['bundle'] . ' не найден');

		$this->logger->add('bundle_path', $bundlePath);
		return $bundlePath;
	}

	// получаем версию бандла
	// используем регулярку для поиска `window.BUNDLE_VERSION = '2.0.1';`
	private function getBundleVersion(string $bundlePath): string
	{
		if (preg_match('/BUNDLE_VERSION\s*=\s*["\']([^"\']+)["\']/', file_get_contents($bundlePath), $matches)) {
			$this->logger->add('matches', $matches);
			$version = $matches[1];
		} else {
			$version =  '1.0.0'; // Дефолтное значение, если не найдено
		}
		$this->logger->add('bundle_version', $version);
		return $version;
	}
}
