<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\RouterTests\RouterTest;

final class CategoryRouterTest extends RouterTest
{
	public function testCategoryWasCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$categoryData = $this->generateCategoryData($user);

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$response = $this->getResponse(
			'category',
			'POST',
			$categoryData
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertArrayHasKey('id', $response['payload']);
		$this->assertArrayHasKey('title', $response['payload']);
		$this->assertArrayHasKey('description', $response['payload']);
		$this->assertArrayHasKey('createdBy', $response['payload']);
		$this->assertEquals(1, $response['payload']['id']);
		$this->assertEquals($categoryData['title'], $response['payload']['title']);
		$this->assertEquals($categoryData['description'], $response['payload']['description']);
		$this->assertIsArray($response['payload']['createdBy']);
	}

	/**
	 * @dataProvider createCategoryProvider
	 */
	public function testCategoryWasNotCreated(array $categoryData, string $message, bool $generateCategory = false): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		if ($generateCategory) {
			$this->generateCategory(null, 'Pokemon', 'The best Pokemon community!');
		}

		$response = $this->getResponse(
			'category',
			'POST',
			$categoryData
		);

		$this->assertEmpty($response['payload']);
		$this->assertEquals($message, $response['message']);
	}

	public function createCategoryProvider()
	{
		yield 'blank user ID' => [
			[
				'createdBy' => 0,
				'title' => 'Pokemon',
				'description' => 'The best Pokemon community!'
			],
			'Cannot create Category: Invalid user ID.'
		];

		yield 'blank title' => [
			[
				'createdBy' => 1,
				'title' => '',
				'description' => 'The best Pokemon community!'
			],
			'Cannot create Category: Missing title.'
		];

		yield 'duplicate title' => [
			[
				'createdBy' => 1,
				'title' => 'Pokemon',
				'description' => 'The best Pokemon community!'
			],
			'Cannot create Category: Title already exists.',
			true
		];
	}

	public function testCategoryWasNotCreatedWhenNotLoggedIn(): void
	{
		$response = $this->getResponse(
			'category',
			'POST',
			$this->generateCategoryData()
		);

		$this->assertEquals('Cannot create Category: You must be logged in.', $response['message']);
		$this->assertEmpty($response['payload']);
	}

	public function testCategoryWasNotCreatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$categoryData = $this->generateCategoryData($userA);

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$createdCategory = $this->getResponse(
			'category',
			'POST',
			$categoryData
		);

		$this->assertEquals('Cannot create Category: You cannot create a category for someone else!', $createdCategory['message']);
		$this->assertEmpty($createdCategory['payload']);
	}

	public function testCategoryWasFoundById(): void
	{
		$category = $this->generateCategory();

		$retrievedCategory = $this->getResponse('category/' . $category->getId())['payload'];

		$this->assertArrayHasKey('id', $retrievedCategory);
		$this->assertArrayHasKey('title', $retrievedCategory);
		$this->assertArrayHasKey('description', $retrievedCategory);
		$this->assertEquals($category->getId(), $retrievedCategory['id']);
		$this->assertEquals($category->getTitle(), $retrievedCategory['title']);
		$this->assertEquals($category->getDescription(), $retrievedCategory['description']);
	}

	public function testCategoryWasNotFoundByWrongId(): void
	{
		$retrievedCategory = $this->getResponse('category/1');

		$this->assertEquals('Cannot find Category: Category does not exist with ID 1.', $retrievedCategory['message']);
		$this->assertEmpty($retrievedCategory['payload']);
	}

	/**
	 * @dataProvider updatedCategoryProvider
	 */
	public function testCategoryWasUpdated(array $oldCategoryData, array $newCategoryData, array $editedFields): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$oldCategoryData['createdBy'] = $user->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$oldCategory = $this->getResponse(
			'category',
			'POST',
			$oldCategoryData
		)['payload'];

		$editedCategory = $this->getResponse(
			'category/' . $oldCategory['id'],
			'PUT',
			$newCategoryData
		)['payload'];

		/**
		 * Check every Category field against all the fields that were supposed to be edited.
		 * If the Category field is a field that's supposed to be edited, check if they're not equal.
		 * If the Category field is not supposed to be edited, check if they're equal.
		 */
		foreach ($oldCategory as $oldCategoryKey => $oldCategoryValue) {
			foreach ($editedFields as $editedField) {
				if ($oldCategoryKey === $editedField) {
					$this->assertNotEquals($oldCategoryValue, $editedCategory[$editedField]);
					$this->assertEquals($editedCategory[$editedField], $newCategoryData[$editedField]);
				}
			}
		}
	}

	public function updatedCategoryProvider()
	{
		yield 'valid title' => [
			['title' => 'Pokemon', 'description' => 'The best Pokemon community!'],
			['title' => 'Pokeyman'],
			['title'],
		];

		yield 'valid description' => [
			['title' => 'Pokemon', 'description' => 'The best Pokemon community!'],
			['description' => 'The #1 Pokemon community!'],
			['description'],
		];

		yield 'valid title and description' => [
			['title' => 'Pokemon', 'description' => 'The best Pokemon community!'],
			['title' => 'Pokeyman', 'description' => 'The #1 Pokemon community!'],
			['title', 'description'],
		];
	}

	/**
	 * @dataProvider updateCategoryProvider
	 */
	public function testCategoryWasNotUpdated(int $categoryId, array $newCategoryData, string $message): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$this->generateCategory($user);

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$editedCategory = $this->getResponse(
			'category/' . $categoryId,
			'PUT',
			$newCategoryData
		);

		$this->assertEquals($message, $editedCategory['message']);
		$this->assertEmpty($editedCategory['payload']);
	}

	public function updateCategoryProvider()
	{
		yield 'blank title' => [
			1,
			['title' => ''],
			'Cannot edit Category: Missing title.'
		];

		yield 'invalid ID' => [
			999,
			['title' => 'Pokemon'],
			'Cannot edit Category: Category does not exist with ID 999.'
		];
	}

	public function testCategoryWasNotUpdatedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$category = $this->generateCategory($user);

		$editedCategory = $this->getResponse(
			'category/' . $category->getId(),
			'PUT',
			$this->generateCategoryData()
		);

		$this->assertEquals('Cannot edit Category: You must be logged in.', $editedCategory['message']);
		$this->assertEmpty($editedCategory['payload']);
	}

	public function testCategoryWasNotUpdatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$category = $this->generateCategory($userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$editedCategory = $this->getResponse(
			'category/' . $category->getId(),
			'PUT',
			$this->generateCategoryData()
		);

		$this->assertEquals('Cannot edit Category: You cannot edit a category that you did not create!', $editedCategory['message']);
		$this->assertEmpty($editedCategory['payload']);
	}

	public function testCategoryWasDeletedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->assertEmpty($category->getDeletedAt());

		$deletedCategory = $this->getResponse(
			'category/' . $category->getId(),
			'DELETE'
		)['payload'];

		$this->assertEquals($category->getId(), $deletedCategory['id']);
		$this->assertEquals($category->getTitle(), $deletedCategory['title']);
		$this->assertEquals($category->getDescription(), $deletedCategory['description']);

		$retrievedCategory = $this->getResponse('category/' . $category->getId())['payload'];

		$this->assertNotEmpty($retrievedCategory['deletedAt']);
	}

	public function testCategoryWasNotDeletedWithInvalidId(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$deletedCategory = $this->getResponse(
			'category/999',
			'DELETE'
		);

		$this->assertEquals('Cannot delete Category: Category does not exist with ID 999.', $deletedCategory['message']);
		$this->assertEmpty($deletedCategory['payload']);
	}

	public function testCategoryWasNotDeletedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$category = $this->generateCategory($user);

		$deletedCategory = $this->getResponse(
			'category/' . $category->getId(),
			'DELETE',
			$this->generateCategoryData()
		);

		$this->assertEquals('Cannot delete Category: You must be logged in.', $deletedCategory['message']);
		$this->assertEmpty($deletedCategory['payload']);
	}

	public function testCategoryWasNotDeletedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$category = $this->generateCategory($userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$deletedCategory = $this->getResponse(
			'category/' . $category->getId(),
			'DELETE'
		);

		$this->assertEquals('Cannot delete Category: You cannot delete a category that you did not create!', $deletedCategory['message']);
		$this->assertEmpty($deletedCategory['payload']);
	}
}
