<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\RouterTests\RouterTest;

final class UserRouterTest extends RouterTest
{
	public function testHome(): void
	{
		$response = $this->getResponse();

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertEquals('Please feel free to make an account and browse around :)', $response['message']);
	}

	public function testInvalidEndpoint(): void
	{
		$response = $this->getResponse('digimon');

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertEquals('404', $response['message']);
	}

	public function testInvalidHttpMethod(): void
	{
		$response = $this->getResponse(
			'user',
			'PATCH'
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertEquals('404', $response['message']);
	}

	public function testUserWasCreatedSuccessfully(): void
	{
		$randomUser = $this->generateUserData();

		$response = $this->getResponse(
			'user',
			'POST',
			$randomUser
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertArrayHasKey('id', $response['payload']);
		$this->assertArrayHasKey('username', $response['payload']);
		$this->assertArrayHasKey('email', $response['payload']);
		$this->assertEquals(1, $response['payload']['id']);
		$this->assertEquals($randomUser['username'], $response['payload']['username']);
		$this->assertEquals($randomUser['email'], $response['payload']['email']);
	}

	/**
	 * @dataProvider createUserProvider
	 */
	public function testUserWasNotCreated(array $userData, string $message, bool $generateUser = false): void
	{
		if ($generateUser) {
			$this->generateUser('Bulbasaur', 'bulbasaur@pokemon.com', 'Grass123');
		}

		$response = $this->getResponse(
			'user',
			'POST',
			$userData
		);

		$this->assertEmpty($response['payload']);
		$this->assertEquals($message, $response['message']);
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

		$retrievedUser = $this->getResponse('user/' . $user->getId())['payload'];

		$this->assertArrayHasKey('id', $retrievedUser);
		$this->assertArrayHasKey('username', $retrievedUser);
		$this->assertArrayHasKey('email', $retrievedUser);
		$this->assertEquals($user->getId(), $retrievedUser['id']);
		$this->assertEquals($user->getUsername(), $retrievedUser['username']);
		$this->assertEquals($user->getEmail(), $retrievedUser['email']);
	}

	public function testUserWasNotFoundByWrongId(): void
	{
		$userId = rand(1, 100);

		$retrievedUser = $this->getResponse('user/' . $userId);

		$this->assertEquals("Cannot find User: User $userId does not exist.", $retrievedUser['message']);
		$this->assertEmpty($retrievedUser['payload']);
	}

	public function testUserWasFoundByName(): void
	{
		$user = $this->generateUser();

		$retrievedUser = $this->getResponse('user/' . $user->getUsername())['payload'];

		$this->assertArrayHasKey('id', $retrievedUser);
		$this->assertArrayHasKey('username', $retrievedUser);
		$this->assertArrayHasKey('email', $retrievedUser);
		$this->assertEquals($user->getId(), $retrievedUser['id']);
		$this->assertEquals($user->getUsername(), $retrievedUser['username']);
		$this->assertEquals($user->getEmail(), $retrievedUser['email']);
	}

	public function testUserWasNotFoundByWrongName(): void
	{
		$username = $this->generateUserData()['username'];
		$retrievedUser = $this->getResponse(
			'user/' . $username,
			'GET'
		);

		$this->assertEquals("Cannot find User: User $username does not exist.", $retrievedUser['message']);
		$this->assertEmpty($retrievedUser['payload']);
	}

	/**
	 * @dataProvider updatedUserProvider
	 */
	public function testUserWasUpdated(array $oldUserData, array $newUserData, array $editedFields): void
	{
		$oldUser = $this->getResponse(
			'user',
			'POST',
			$oldUserData
		)['payload'];

		$this->getResponse(
			'auth/login',
			'POST',
			$oldUserData
		);

		$editedUser = $this->getResponse(
			'user/' . $oldUser['id'],
			'PUT',
			$newUserData
		)['payload'];

		/**
		 * Check every User field against all the fields that were supposed to be edited.
		 * If the User field is a field that's supposed to be edited, check if they're not equal.
		 * If the User field is not supposed to be edited, check if they're equal.
		 */
		foreach ($oldUser as $oldUserKey => $oldUserValue) {
			foreach ($editedFields as $editedField) {
				if ($oldUserKey === $editedField) {
					$this->assertNotEquals($oldUserValue, $editedUser[$editedField]);
					$this->assertEquals($editedUser[$editedField], $newUserData[$editedField]);
				}
			}
		}
	}

	public function updatedUserProvider()
	{
		yield 'valid username' => [
			['username' => 'Pikachu', 'email' => 'pikachu@pokemon.com', 'password' => 'pikachu123'],
			['username' => 'Bulbasaur'],
			['username'],
		];

		yield 'valid email' => [
			['username' => 'Pikachu', 'email' => 'pikachu@pokemon.com', 'password' => 'pikachu123'],
			['email' => 'bulbasaur@pokemon.com'],
			['email'],
		];

		yield 'valid username and email' => [
			['username' => 'Pikachu', 'email' => 'pikachu@pokemon.com', 'password' => 'pikachu123'],
			['username' => 'Magikarp', 'email' => 'magikarp@pokemon.com'],
			['username', 'email'],
		];
	}

	/**
	 * @dataProvider updateUserProvider
	 */
	public function testUserWasNotUpdated(array $newUserData, string $message): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$editedUser = $this->getResponse(
			'user/' . $user->getId(),
			'PUT',
			$newUserData
		);

		$this->assertEquals($message, $editedUser['message']);
		$this->assertEmpty($editedUser['payload']);
	}

	public function updateUserProvider()
	{
		yield 'blank username' => [
			['username' => ''],
			'Cannot edit User: Missing username.'
		];

		yield 'blank email' => [
			['email' => ''],
			'Cannot edit User: Missing email.'
		];

		yield 'integer username' => [
			['username' => 123],
			'User was not updated.'
		];

		yield 'integer email' => [
			['email' => 123],
			'User was not updated.'
		];
	}

	public function testUserWasNotUpdatedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();

		$editedUser = $this->getResponse(
			'user/' . $user->getId(),
			'PUT',
			$this->generateUserData()
		);

		$this->assertEquals('Cannot edit User: You must be logged in.', $editedUser['message']);
		$this->assertEmpty($editedUser['payload']);
	}

	public function testUserWasNotUpdatedByAnotherUser(): void
	{
		$userA = $this->generateUser(...array_values($this->generateUserData()));
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$editedUser = $this->getResponse(
			'user/' . $userA->getId(),
			'PUT',
			$this->generateUserData()
		);

		$this->assertEquals('Cannot edit User: You cannot edit a user other than yourself!', $editedUser['message']);
		$this->assertEmpty($editedUser['payload']);
	}

	public function testUserWasDeletedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->assertEmpty($user->getDeletedAt());

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$deletedUser = $this->getResponse(
			'user/' . $user->getId(),
			'DELETE'
		)['payload'];

		$this->assertEquals($user->getId(), $deletedUser['id']);
		$this->assertEquals($user->getUsername(), $deletedUser['username']);
		$this->assertEquals($user->getEmail(), $deletedUser['email']);

		$retrievedUser = $this->getResponse('user/' . $user->getId())['payload'];

		$this->assertNotEmpty($retrievedUser['deletedAt']);
	}

	public function testUserWasNotDeletedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser('Bulbasaur', 'bulbasaur@pokemon.com', 'Grass123');

		$deletedUser = $this->getResponse(
			'user/' . $user->getId(),
			'DELETE'
		);

		$this->assertEquals('Cannot delete User: You must be logged in.', $deletedUser['message']);
		$this->assertEmpty($deletedUser['payload']);
	}

	public function testUserWasNotDeletedByAnotherUser(): void
	{
		$userA = $this->generateUser(...array_values($this->generateUserData()));
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$editedUser = $this->getResponse(
			'user/' . $userA->getId(),
			'DELETE'
		);

		$this->assertEquals('Cannot delete User: You cannot delete a user other than yourself!', $editedUser['message']);
		$this->assertEmpty($editedUser['payload']);
	}
}
