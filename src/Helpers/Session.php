<?php

class Session
{
	public static function start(): void
	{
		session_start();
	}

	public static function destroy(): void
	{
		if (self::isSessionStarted()) {
			session_unset();
			session_destroy();
		}
	}

	public static function add(string $key, $value): void
	{
		$_SESSION[$key] = $value;
	}

	public static function remove(string $key): void
	{
		unset($_SESSION[$key]);
	}

	public static function get(string $key)
	{
		return $_SESSION[$key] ?? '';
	}

	public static function exists(string $key): bool
	{
		return isset($_SESSION[$key]);
	}

	public static function isSessionStarted(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}
}
