<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSix\Models\User;
use AssignmentSixTests\BrowserTests\BrowserTest;

final class UserBrowserTest extends BrowserTest
{
	public function testHome(): void
	{
		$this->goTo('/');

		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/login\"]"));
		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/register\"]"));

		$h1 = $this->findElement('h1');
		$this->assertStringContainsString('Welcome to Reddit!', $h1->getText());
	}

	public function testInvalidEndpoint(): void
	{
		$this->goTo('digimon');

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString('404', $body->getText());
	}

	public function testUserWasCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();

		$this->goTo('/');
		$this->clickOnLink('auth/register');

		$h1 = $this->findElement('h1');
		$usernameInput = $this->findElement("form#new-user-form input[name=\"username\"]");
		$emailInput = $this->findElement("form#new-user-form input[name=\"email\"]");
		$passwordInput = $this->findElement("form#new-user-form input[name=\"password\"]");
		$submitButton = $this->findElement("form#new-user-form button");

		$this->assertStringContainsString('Register', $h1->getText());

		$usernameInput->sendKeys($userData['username']);
		$emailInput->sendKeys($userData['email']);
		$passwordInput->sendKeys($userData['password']);
		$submitButton->click();

		$user = User::findByUsername($userData['username']);
		$h1 = $this->findElement("h1");

		$this->assertStringContainsString('Login', $h1->getText());
		$this->assertEquals($userData['username'], $user->getUsername());
		$this->assertEquals($userData['email'], $user->getEmail());
	}

	/**
	 * @dataProvider createUserProvider
	 */
	public function testUserWasNotCreated(array $userData, string $message, bool $generateUser = false): void
	{
		if ($generateUser) {
			self::generateUser('Bulbasaur', 'bulbasaur@pokemon.com', 'Grass123');
		}

		$this->goTo('/');
		$this->clickOnLink('auth/register');

		$h1 = $this->findElement('h1');
		$usernameInput = $this->findElement("form#new-user-form input[name=\"username\"]");
		$emailInput = $this->findElement("form#new-user-form input[name=\"email\"]");
		$passwordInput = $this->findElement("form#new-user-form input[name=\"password\"]");
		$submitButton = $this->findElement("form#new-user-form button");

		$this->assertStringContainsString('Register', $h1->getText());

		$usernameInput->sendKeys($userData['username']);
		$emailInput->sendKeys($userData['email']);
		$passwordInput->sendKeys($userData['password']);

		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString($message, $body->getText());
	}

	public function createUserProvider()
	{
		yield 'blank username' => [
			[
				'username' => '',
				'email' => 'bulbasaur@pokemon.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Missing username.'
		];

		yield 'blank email' => [
			[
				'username' => 'Bulbasaur',
				'email' => '',
				'password' => 'Grass123'
			],
			'Cannot create User: Missing email.'
		];

		yield 'blank password' => [
			[
				'username' => 'Bulbasaur',
				'email' => 'bulbasaur@pokemon.com',
				'password' => ''
			],
			'Cannot create User: Missing password.'
		];

		yield 'duplicate username' => [
			[
				'username' => 'Bulbasaur',
				'email' => 'bulbasaur1@pokemon.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Username already exists.',
			true
		];

		yield 'duplicate email' => [
			[
				'username' => 'Bulbasaur1',
				'email' => 'bulbasaur@pokemon.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Email already exists.',
			true
		];
	}

	public function testUserWasFoundById(): void
	{
		$user = $this->generateUser();
		$userId = $user->getId();

		$this->goTo('user/{id}', ['id' => $userId]);

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($user->getUsername(), $username->getText());
		$this->assertStringContainsString($user->getEmail(), $email->getText());
	}

	public function testUserWasNotFoundByWrongId(): void
	{
		$randomUserId = rand(1, 100);

		$this->goTo('user/{id}', ['id' => $randomUserId]);

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot find User: User $randomUserId does not exist.", $body->getText());
	}

	public function testUserWasFoundByUsername(): void
	{
		$user = $this->generateUser();
		$username = $user->getUsername();

		$this->goTo('user/{username}', ['username' => $username]);

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($user->getUsername(), $username->getText());
		$this->assertStringContainsString($user->getEmail(), $email->getText());
	}

	public function testUserWasNotFoundByWrongUsername(): void
	{
		$randomUsername = $this->generateUserData()['username'];

		$this->goTo('user/{username}', ['username' => $randomUsername]);

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot find User: User $randomUsername does not exist.", $body->getText());
	}

	public function testUserWasLoggedInAndOutSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->goTo('/');

		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/login\"]"));
		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/register\"]"));
		$this->assertFalse($this->doesElementExist("nav a[href*=\"auth/logout\"]"));

		$this->logIn($userData['email'], $userData['password']);

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($user->getUsername(), $username->getText());
		$this->assertStringContainsString($user->getEmail(), $email->getText());

		$this->assertFalse($this->doesElementExist("nav a[href*=\"auth/login\"]"));
		$this->assertFalse($this->doesElementExist("nav a[href*=\"auth/register\"]"));
		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/logout\"]"));

		$this->logOut();

		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/login\"]"));
		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/register\"]"));
		$this->assertFalse($this->doesElementExist("nav a[href*=\"auth/logout\"]"));
	}

	/**
	 * @dataProvider invalidCredentialsProvider
	 */
	public function testUserWasNotLoggedIn(array $credentials, string $message): void
	{
		$this->generateUser('Pikachu', 'pikachu@pokemon.com', 'Electric123');
		$this->logIn($credentials['email'], $credentials['password']);

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString($message, $body->getText());
	}

	public function invalidCredentialsProvider()
	{
		yield 'blank form' => [
			[
				'email' => '',
				'password' => ''
			],
			'Cannot log in: Missing email.'
		];

		yield 'blank email' => [
			[
				'email' => '',
				'password' => 'Electric123'
			],
			'Cannot log in: Missing email.'
		];

		yield 'blank password' => [
			[
				'email' => 'pikachu@pokemon.com',
				'password' => ''
			],
			'Cannot log in: Missing password.'
		];

		yield 'wrong email' => [
			[
				'email' => 'pikchu@pokemon.com',
				'password' => 'Electric123'
			],
			'Cannot log in: Invalid credentials.'
		];

		yield 'wrong password' => [
			[
				'email' => 'pikachu@pokemon.com',
				'password' => 'Electric1234'
			],
			'Cannot log in: Invalid credentials.'
		];
	}

	public function testUserWasUpdatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();
		$newUserData = $this->generateUserData();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('user/{id}', ['id' => $userId]);

		$usernameInput = $this->findElement("form#edit-user-form input[name=\"username\"]");
		$emailInput = $this->findElement("form#edit-user-form input[name=\"email\"]");
		$passwordInput = $this->findElement("form#edit-user-form input[name=\"password\"]");
		$submitButton = $this->findElement("form#edit-user-form button");

		$usernameInput->sendKeys($newUserData['username']);
		$emailInput->sendKeys($newUserData['email']);
		$passwordInput->sendKeys($newUserData['password']);
		$submitButton->click();

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($newUserData['username'], $username->getText());
		$this->assertStringContainsString($newUserData['email'], $email->getText());

		$this->logOut();
	}

	public function testUserWasNotUpdatedWithBlankUsername(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('user/{id}', ['id' => $userId]);

		$usernameInput = $this->findElement("form#edit-user-form input[name=\"username\"]");
		$submitButton = $this->findElement("form#edit-user-form button");

		$usernameInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot edit User: Missing username.", $body->getText());

		$this->logOut();
	}

	public function testUserWasNotUpdatedWithBlankEmail(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('user/{id}', ['id' => $userId]);

		$emailInput = $this->findElement("form#edit-user-form input[name=\"email\"]");
		$submitButton = $this->findElement("form#edit-user-form button");

		$emailInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot edit User: Missing email.", $body->getText());

		$this->logOut();
	}

	public function testUserCanNotSeeUpdateInterfaceWhenNotLoggedIn(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();

		$this->goTo('user/{id}', ['id' => $userId]);

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($userData['username'], $username->getText());
		$this->assertStringContainsString($userData['email'], $email->getText());
		$this->assertFalse($this->doesElementExist("form#edit-user-form"));
		$this->assertFalse($this->doesElementExist("form#edit-user-form input[name=\"username\"]"));
		$this->assertFalse($this->doesElementExist("form#edit-user-form input[name=\"email\"]"));
		$this->assertFalse($this->doesElementExist("form#edit-user-form input[name=\"password\"]"));
		$this->assertFalse($this->doesElementExist("form#edit-user-form button"));
	}

	public function testUserWasDeletedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('user/{id}', ['id' => $userId]);
		$this->clickOnButton("form#delete-user-form button");

		$deletedAt = User::findById($userId)->getDeletedAt();
		$body = $this->findElement('body');

		$this->assertStringContainsString("User was deleted on $deletedAt", $body->getText());
		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/login\"]"));
		$this->assertTrue($this->doesElementExist("nav a[href*=\"auth/register\"]"));
		$this->assertFalse($this->doesElementExist("nav a[href*=\"auth/logout\"]"));
	}

	public function testUserCanNotSeeDeleteInterfaceWhenNotLoggedIn(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();

		$this->goTo('user/{id}', ['id' => $userId]);

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($userData['username'], $username->getText());
		$this->assertStringContainsString($userData['email'], $email->getText());
		$this->assertFalse($this->doesElementExist("form#delete-user-form"));
		$this->assertFalse($this->doesElementExist("form#delete-user-form button"));
	}

	public function testDeletedUserCanNotLogIn(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('user/{id}', ['id' => $userId]);
		$this->clickOnButton("form#delete-user-form button");

		$this->logIn($userData['email'], $userData['password']);

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot log in: User has been deleted.", $body->getText());
	}
}
