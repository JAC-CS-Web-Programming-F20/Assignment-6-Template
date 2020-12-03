<?php

declare(strict_types=1);

/**
 * Support class to help with handling Url paths.
 *
 * Class Url
 */
class Url
{
	/**
	 * Current public path of the application.
	 */
	private const PUBLIC_PATH = 'Assignments/assignment-6-githubusername/public/';

	/**
	 * Relative path to where the CSS Styles exist.
	 */
	private const STYLES_PATH = 'styles/';

	/**
	 * Relative path to where the JS Scripts exist.
	 */
	private const SCRIPTS_PATH = 'js/';

	/**
	 * Relative path to where the images exist.
	 */
	private const IMAGES_PATH = 'images/';

	/**
	 * Generates the base url of the application.
	 *
	 * @return string
	 */
	public static function base(): string
	{
		return sprintf(
			'%s://%s/%s',
			isset($_SERVER['SERVER_PROTOCOL']) && strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') !== false ? 'https' : 'http',
			$_SERVER['SERVER_NAME'] ?? 'apache',
			self::PUBLIC_PATH
		);
	}

	/**
	 * Generates a full url to the desired path.
	 * Can pass optional parameters to map to tokens in url path.
	 *
	 * @param string $path
	 * @param array $parameters
	 * @return string
	 */
	public static function path(string $path = '', array $parameters = []): string
	{
		$url = self::base() . trim($path, '/');

		if (empty($parameters)) {
			return $url;
		}

		// TODO: look into implementing some way for query parameters maybe?
		foreach ($parameters as $key => $value) {
			$url = str_replace('{' . $key . '}', $value, $url);
		}

		return $url;
	}

	/**
	 * Generates a full url to the desired styles path.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function styles(string $path): string
	{
		return self::path(self::STYLES_PATH . $path);
	}

	/**
	 * Generates a full url to the desired scripts path.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function scripts(string $path): string
	{
		return self::path(self::SCRIPTS_PATH . $path);
	}

	/**
	 * Generates a full url to the desired images path.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function images(string $path): string
	{
		return self::path(self::IMAGES_PATH . $path);
	}
}
