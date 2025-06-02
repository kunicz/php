<?php

namespace php2steblya;

use php2steblya\Script;
use php2steblya\Logger;

class ScriptFactory
{
	public static function initClass(array $_GET_scriptData = []): Script
	{
		if (empty($_GET_scriptData['script'])) throw new \Exception('параметр (script) не передан');

		$path = trim($_GET_scriptData['script'], '/');
		$className = self::resolveClassName($path);
		$scriptInstance = new $className($_GET_scriptData);

		if (!method_exists($scriptInstance, 'init')) throw new \Exception("метод (init) не найден в классе ($className)");

		$scriptInstance->init();
		return $scriptInstance;
	}

	// определяет полное имя класса скрипта на основе пути
	private static function resolveClassName(string $path): string
	{
		$parts = array_filter(explode('/', $path));
		$fileName = array_pop($parts); // последний элемент — имя класса
		$serviceDir = strtolower($fileName); // потенциальное имя подпапки

		if (empty($parts) && is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . $serviceDir)) {
			// если путь не содержит слешей (только имя) и существует подпапка с таким именем
			$namespace = '\\' . $serviceDir;
			$fileName = ucfirst($fileName);
		} else {
			// иначе собираем namespace из пути (если он есть)
			$namespace = empty($parts) ? '' : '\\' . implode('\\', $parts);
		}

		$className = 'php2steblya\\scripts' . $namespace . '\\' . $fileName;
		Logger::getInstance()->addRoot('script_class', $className);
		if (!class_exists($className)) throw new \Exception("скрипт ($path) не найден");

		return $className;
	}
}
