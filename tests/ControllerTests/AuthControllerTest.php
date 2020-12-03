<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\AuthController;
use AssignmentSix\Exceptions\AuthException;
use AssignmentSix\Router\JsonResponse;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class AuthControllerTest extends ControllerTest
{
	public function testAuthControllerCalledGetRegisterForm(): void
	{
		$request = $this->createMockRequest(['register']);
		$controller = new AuthController($request, new JsonResponse());

		$this->assertEquals('getRegisterForm', $controller->getAction());
	}

	public function testAuthControllerCalledGetLoginForm(): void
	{
		$request = $this->createMockRequest(['login']);
		$controller = new AuthController($request, new JsonResponse());

		$this->assertEquals('getLoginForm', $controller->getAction());
	}

	public function testAuthControllerCalledLogIn(): void
	{
		$request = $this->createMockRequest(['login'], 'POST');
		$controller = new AuthController($request, new JsonResponse());

		$this->assertEquals('logIn', $controller->getAction());
	}

	public function testAuthControllerCalledLogOut(): void
	{
		$request = $this->createMockRequest(['logout']);
		$controller = new AuthController($request, new JsonResponse());

		$this->assertEquals('logOut', $controller->getAction());
	}

	/**
	 * @dataProvider loginProvider
	 */
	public function testExceptionWasThrownLoggingIn(array $parameters, string $message): void
	{
		$this->generateUser('Pikachu', 'pikachu@pokemon.com', 'Electric123');

		$request = $this->createMockRequest(['login'], 'POST', $parameters);
		$controller = new AuthController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(AuthException::class, $message, $controller, 'doAction'));
	}

	public function loginProvider()
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

	public function testSessionVariableWasSetOnLogin(): void
	{
		$user = $this->generateUser('Pikachu', 'pikachu@pokemon.com', 'Electric123');

		$request = $this->createMockRequest(['login'], 'POST', ['email' => 'pikachu@pokemon.com', 'password' => 'Electric123']);
		$controller = new AuthController($request, new JsonResponse());

		$controller->doAction();

		$this->assertTrue(isset($_SESSION['user_id']));
		$this->assertEquals($user->getId(), $_SESSION['user_id']);
	}

	public function testSessionWasDestroyedOnLogout(): void
	{
		$this->generateUser('Pikachu', 'pikachu@pokemon.com', 'Electric123');

		$request = $this->createMockRequest(['login'], 'POST', ['email' => 'pikachu@pokemon.com', 'password' => 'Electric123']);
		$controller = new AuthController($request, new JsonResponse());

		$controller->doAction();

		$this->assertTrue(isset($_SESSION['user_id']));

		$request = $this->createMockRequest(['logout']);
		$controller = new AuthController($request, new JsonResponse());

		$controller->doAction();

		$this->assertFalse(isset($_SESSION['user_id']));
	}
}
