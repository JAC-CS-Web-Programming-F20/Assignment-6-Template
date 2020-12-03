<?php

namespace AssignmentSixTests\BrowserTests;

use AssignmentSixTests\AssignmentSixTest;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use Faker\Factory;

class BrowserTest extends AssignmentSixTest
{
	protected static RemoteWebDriver $driver;

	public static function setUpBeforeClass(): void
	{
		self::$driver = RemoteWebDriver::create("http://firefox:4444/wd/hub", DesiredCapabilities::firefox());
		self::$faker = Factory::create();
	}

	public static function tearDownAfterClass(): void
	{
		self::$driver->close();
	}

	protected function findElement(string $selector): RemoteWebElement
	{
		$element = self::$driver->findElement(WebDriverBy::cssSelector($selector));

		$this->scrollTo($element);
		usleep(500000);
		return $element;
	}

	protected function findSelectElement(string $selector): WebDriverSelect
	{
		return new WebDriverSelect($this->findElement($selector));
	}

	protected function findElements(string $selector): array
	{
		return self::$driver->findElements(WebDriverBy::cssSelector($selector));
	}

	protected function findElementByLink(string $path, array $parameters = []): RemoteWebElement
	{
		return $this->findElement("a[href*=\"" . \Url::path($path, $parameters) . "\"]");
	}

	protected function clickOnLink(string $path, array $parameters = []): void
	{
		$this->findElementByLink($path, $parameters)->click();
		usleep(500000);
	}

	protected function clickOnButton(string $selector): void
	{
		$this->findElement($selector)->click();
		usleep(500000);
	}

	protected function doesElementExist(string $selector): bool
	{
		try {
			$this->findElement($selector);
		} catch (NoSuchElementException $noSuchElementException) {
			return false;
		}

		return true;
	}

	protected function scrollTo(RemoteWebElement $element): void
	{
		self::$driver->executeScript("arguments[0].scrollIntoView();", [$element]);
	}

	protected function goTo(string $path, array $parameters = []): void
	{
		self::$driver->get(\Url::path($path, $parameters));
	}

	protected function refresh(): void
	{
		self::$driver->navigate()->refresh();
	}

	protected function goBack(): void
	{
		self::$driver->navigate()->back();
	}

	protected function getCurrentUrl(): string
	{
		return self::$driver->getCurrentURL();
	}

	protected function logIn(string $email, string $password): void
	{
		$this->goTo('/');
		$this->clickOnLink('auth/login');

		$emailInput = $this->findElement("form#login-form input[name=\"email\"]");
		$passwordInput = $this->findElement("form#login-form input[name=\"password\"]");
		$submitButton = $this->findElement("form#login-form button");

		$emailInput->sendKeys($email);
		usleep(500000);
		$passwordInput->sendKeys($password);
		usleep(500000);
		$submitButton->click();
	}

	protected function logOut(): void
	{
		$this->goTo('/');
		$this->clickOnLink('auth/logout');
	}

	protected function waitUntilElementChanges(RemoteWebElement $element, string $value): void
	{
		self::$driver->wait()->until(
			/**
			 * This function will be called continuously until the condition is met.
			 */
			function () use ($element, $value) {
				return $element->getText() !== $value;
			}
		);
	}

	protected function waitUntilElementLoads(string $selector): void
	{
		self::$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector($selector))
		);
	}
}
