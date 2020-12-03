<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\UserController;
use AssignmentSix\Exceptions\UserException;
use AssignmentSix\Models\User;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class UserControllerTest extends ControllerTest
{
	public function testUserControllerCalledShow(): void
	{
		$user = $this->generateUser();
		$request = $this->createMockRequest([$user->getId()]);
		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was retrieved successfully!', $response->getMessage());
		$this->assertEquals($user->getId(), $response->getPayload()->getId());
		$this->assertEquals($user->getUsername(), $response->getPayload()->getUsername());
		$this->assertEquals($user->getEmail(), $response->getPayload()->getEmail());
	}

	/**
	 * @dataProvider findUserProvider
	 */
	public function testExceptionWasThrownShowingUser(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);

		(new UserController($request, new JsonResponse()))->doAction();
	}

	public function findUserProvider()
	{
		yield 'invalid ID' => [
			UserException::class,
			'Cannot find User: User 1 does not exist.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testUserControllerCalledNew(): void
	{
		$user = $this->generateUserData();

		$request = $this->createMockRequest([], 'POST', [
			'username' => $user['username'],
			'email' => $user['email'],
			'password' => $user['password']
		]);
		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was created successfully!', $response->getMessage());
		$this->assertEquals($user['username'], $response->getPayload()->getUsername());
		$this->assertEquals($user['email'], $response->getPayload()->getEmail());
		$this->assertNotEmpty($response->getPayload()->getId());
	}

	/**
	 * @dataProvider createUserProvider
	 */
	public function testExceptionWasThrownNewingUser(string $exception, string $message, string $requestMethod, array $parameters, bool $generateUser = false): void
	{
		if ($generateUser) {
			self::generateUser('Charmeleon', 'charmeleon@pokemon.com', 'Fire123');
		}

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new UserController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function createUserProvider()
	{
		yield 'blank username' => [
			UserException::class,
			'Cannot create User: Missing username.',
			'POST',
			[
				'body' => [
					'username' => '',
					'email' => 'bulbasaur@pokemon.com',
					'password' => 'Grass123'
				],
				'header' => []
			],
		];

		yield 'blank email' => [
			UserException::class,
			'Cannot create User: Missing email.',
			'POST',
			[
				'body' => [
					'username' => 'Blastoise',
					'email' => '',
					'password' => 'Water123'
				],
				'header' => []
			],
		];

		yield 'blank password' => [
			UserException::class,
			'Cannot create User: Missing password.',
			'POST',
			[
				'body' => [
					'username' => 'Charmeleon',
					'email' => 'charmeleon@pokemon.com',
					'password' => ''
				],
				'header' => []
			],
		];

		yield 'duplicate username' => [
			UserException::class,
			'Cannot create User: Username already exists.',
			'POST',
			[
				'body' => [
					'username' => 'Charmeleon',
					'email' => 'charmeleon1@pokemon.com',
					'password' => 'Fire123'
				],
				'header' => []
			],
			true
		];

		yield 'duplicate email' => [
			UserException::class,
			'Cannot create User: Email already exists.',
			'POST',
			[
				'body' => [
					'username' => 'Charmeleon1',
					'email' => 'charmeleon@pokemon.com',
					'password' => 'Fire123'
				],
				'header' => []
			],
			true
		];
	}

	public function testUserControllerCalledEdit(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$newUserData = $this->generateUserData();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$user->getId()], 'PUT', [
			'username' => $newUserData['username'],
			'email' => $newUserData['email']
		]);
		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was updated successfully!', $response->getMessage());
		$this->assertEquals($newUserData['username'], $response->getPayload()->getUsername());
		$this->assertEquals($newUserData['email'], $response->getPayload()->getEmail());
		$this->assertNotEquals($user->getUsername(), $response->getPayload()->getUsername());
		$this->assertNotEquals($user->getEmail(), $response->getPayload()->getEmail());
	}

	/**
	 * @dataProvider editUserProvider
	 */
	public function testExceptionWasThrownEditingUser(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$userData = $this->generateUserData('Bulbasaur', 'bulbasaur@pokemon.com', 'Grass123');

		$this->generateUser(...array_values($userData));
		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new UserController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function editUserProvider()
	{
		yield 'blank username' => [
			UserException::class,
			'Cannot edit User: Missing username.',
			'PUT',
			[
				'body' => [
					'username' => '',
					'email' => 'bulbasaur@pokemon.com',
					'password' => 'Grass123'
				],
				'header' => [1]
			],
		];

		yield 'blank email' => [
			UserException::class,
			'Cannot edit User: Missing email.',
			'PUT',
			[
				'body' => [
					'username' => 'Bulbasaur',
					'email' => '',
					'password' => 'Grass123'
				],
				'header' => [1]
			],
		];
	}

	public function testUserWasNotUpdatedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$newUserData = $this->generateUserData();

		$request = $this->createMockRequest([$user->getId()], 'PUT', [
			'username' => $newUserData['username'],
			'email' => $newUserData['email']
		]);

		$controller = new UserController($request, new JsonResponse());
		$response = $controller->doAction();

		$this->assertEquals('Cannot edit User: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenUpdatingAnotherUser(): void
	{
		$userA = $this->generateUser(...array_values($this->generateUserData()));
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$newUserData = $this->generateUserData();

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$userA->getId()], 'PUT', [
			'username' => $newUserData['username'],
			'email' => $newUserData['email']
		]);
		$controller = new UserController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			UserException::class,
			'Cannot edit User: You cannot edit a user other than yourself!',
			$controller,
			'doAction'
		));
	}

	public function testUserControllerCalledDestroy(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$user->getId()], 'DELETE');
		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was deleted successfully!', $response->getMessage());
		$this->assertEquals($user->getId(), $response->getPayload()->getId());
		$this->assertEquals($user->getUsername(), $response->getPayload()->getUsername());
		$this->assertEquals($user->getEmail(), $response->getPayload()->getEmail());
		$this->assertNull($user->getDeletedAt());
		$this->assertNotNull(User::findById($user->getId())->getDeletedAt());
	}

	public function testUserWasNotDestroyedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser('Bulbasaur', 'bulbasaur@pokemon.com', 'Grass123');

		$request = $this->createMockRequest([$user->getId()], 'DELETE');
		$response = (new UserController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot delete User: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenDestroyingAnotherUser(): void
	{
		$userA = $this->generateUser(...array_values($this->generateUserData()));
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$userA->getId()], 'DELETE');
		$controller = new UserController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			UserException::class,
			'Cannot delete User: You cannot delete a user other than yourself!',
			$controller,
			'doAction'
		));
	}
}
