<?php

namespace php2steblya\api;

use php2steblya\Exception;

/**
 * Interface ResponseDecoderInterface
 * Интерфейс для декодеров ответов API.
 */
interface ResponseDecoderInterface
{
	/**
	 * Декодирует ответ API.
	 *
	 * @param string $response Ответ от API.
	 * @return mixed Декодированные данные.
	 * @throws Exception В случае ошибки декодирования.
	 */
	public static function decode(string $response);

	/**
	 * Проверяет, поддерживает ли декодер данный тип ответа.
	 *
	 * @param string $response Ответ от API.
	 * @return bool True, если декодер поддерживает этот тип ответа.
	 */
	public static function supports(string $response): bool;
}

/**
 * Class JsonResponseDecoder
 * Декодер для обработки JSON-ответов.
 */
class JsonResponseDecoder implements ResponseDecoderInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function decode(string $response)
	{
		$decoded = json_decode($response, false);
		if (json_last_error() !== JSON_ERROR_NONE) throw new \Exception('JSON decode error: ' . json_last_error());
		return $decoded;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function supports(string $response): bool
	{
		return substr(trim($response), 0, 1) === '{'; // Проверка на JSON
	}
}

/**
 * Class GzipResponseDecoder
 * Декодер для обработки gzip-сжатых ответов.
 */
class GzipResponseDecoder implements ResponseDecoderInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function decode(string $response)
	{
		$decoded = @gzdecode($response);
		if ($decoded === false) throw new \Exception('gzip decode error');
		return JsonResponseDecoder::supports($decoded) ? JsonResponseDecoder::decode($decoded) : $decoded;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function supports(string $response): bool
	{
		return substr($response, 0, 2) === "\x1f\x8b"; // Проверка на gzip
	}
}

/**
 * Class ApiResponseHandler
 * Класс для обработки и проверки ответов API.
 */
class ApiResponseHandler
{
	/**
	 * @var array Список декодеров для обработки ответов API.
	 */
	private static array $decoders = [
		JsonResponseDecoder::class,
		GzipResponseDecoder::class,
	];

	/**
	 * Декодирует ответ от API с использованием подходящего декодера.
	 *
	 * @param string $response Ответ от API.
	 * @return mixed Декодированные данные или оригинальный ответ.
	 * @throws Exception Если декодер не может обработать ответ.
	 */
	public static function decode(string $response)
	{
		foreach (self::$decoders as $decoderClass) {
			if ($decoderClass::supports($response)) {
				return $decoderClass::decode($response);
			}
		}
		return $response;
	}

	/**
	 * Приводит ответ к объекту, если это строка.
	 *
	 * @param mixed $response Ответ от API.
	 * @return object Ответ в виде объекта.
	 */
	public static function ensureObject($response): object
	{
		if (is_string($response)) return (object)['response' => $response];
		return $response;
	}

	/**
	 * Проверяет корректность ответа от API.
	 *
	 * @param mixed $response Ответ от API.
	 * @return object Проверенные данные в виде объекта.
	 * @throws Exception В случае пустого или некорректного формата ответа.
	 */
	public static function check($response): object
	{
		if (empty($response)) throw new \Exception('Пустой ответ от API');
		if (!is_object($response)) throw new \Exception('Ответ от API не объект');
		return $response;
	}
}
