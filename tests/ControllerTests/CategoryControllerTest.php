<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\CategoryController;
use AssignmentSix\Exceptions\CategoryException;
use AssignmentSix\Models\Category;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class CategoryControllerTest extends ControllerTest
{
	public function testCategoryControllerCalledShow(): void
	{
		$category = $this->generateCategory();
		$request = $this->createMockRequest([$category->getId()]);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was retrieved successfully!', $response->getMessage());
		$this->assertEquals($category->getId(), $response->getPayload()->getId());
		$this->assertEquals($category->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($category->getDescription(), $response->getPayload()->getDescription());
	}

	/**
	 * @dataProvider findCategoryProvider
	 */
	public function testExceptionWasThrownShowingCategory(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function findCategoryProvider()
	{
		yield 'invalid ID' => [
			CategoryException::class,
			'Cannot find Category: Category does not exist with ID 1.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testCategoryControllerCalledNew(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategoryData($user);

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([], 'POST', [
			'createdBy' => $category['createdBy'],
			'title' => $category['title'],
			'description' => $category['description']
		]);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was created successfully!', $response->getMessage());
		$this->assertEquals($category['title'], $response->getPayload()->getTitle());
		$this->assertEquals($category['description'], $response->getPayload()->getDescription());
		$this->assertNotEmpty($response->getPayload()->getId());
		$this->assertNotEmpty(Category::findById($response->getPayload()->getId())->getCreatedAt());
	}

	/**
	 * @dataProvider createCategoryProvider
	 */
	public function testExceptionWasThrownNewingCategory(string $exception, string $message, string $requestMethod, array $parameters, bool $generateCategory = false): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		if ($generateCategory) {
			$this->generateCategory($user, 'Pokemon');
		}

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function createCategoryProvider()
	{
		yield 'blank user ID' => [
			CategoryException::class,
			'Cannot create Category: Invalid user ID.',
			'POST',
			[
				'body' => [
					'createdBy' => 0,
					'title' => 'Pokemon',
					'description' => 'The best Pokemon community!'
				],
				'header' => []
			],
		];

		yield 'blank title' => [
			CategoryException::class,
			'Cannot create Category: Missing title.',
			'POST',
			[
				'body' => [
					'createdBy' => 1,
					'title' => '',
					'description' => 'The best Pokemon community!'
				],
				'header' => []
			],
		];

		yield 'duplicate title' => [
			CategoryException::class,
			'Cannot create Category: Title already exists.',
			'POST',
			[
				'body' => [
					'createdBy' => 1,
					'title' => 'Pokemon',
					'description' => 'The best Pokemon community!'
				],
				'header' => []
			],
			true
		];
	}

	public function testCategoryWasNotCreatedWhenNotLoggedIn(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$categoryData = $this->generateCategoryData($user);
		$request = $this->createMockRequest([], 'POST', [
			'createdBy' => $categoryData['createdBy'],
			'title' => $categoryData['title'],
			'description' => $categoryData['description']
		]);
		$controller = new CategoryController($request, new JsonResponse());
		$response = $controller->doAction();

		$this->assertEquals('Cannot create Category: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenCategoryCreatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$categoryData = $this->generateCategoryData($userA);

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([], 'POST', [
			'createdBy' => $categoryData['createdBy'],
			'title' => $categoryData['title'],
			'description' => $categoryData['description']
		]);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CategoryException::class,
			'Cannot create Category: You cannot create a category for someone else!',
			$controller,
			'doAction'
		));
	}

	public function testCategoryControllerCalledEdit(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);
		$newCategoryData = $this->generateCategoryData();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$user->getId()], 'PUT', [
			'title' => $newCategoryData['title'],
			'description' => $newCategoryData['description']
		]);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was updated successfully!', $response->getMessage());
		$this->assertEquals($newCategoryData['title'], $response->getPayload()->getTitle());
		$this->assertEquals($newCategoryData['description'], $response->getPayload()->getDescription());
		$this->assertNotEquals($category->getTitle(), $response->getPayload()->getDescription());
		$this->assertNotEquals($category->getDescription(), $response->getPayload()->getTitle());
		$this->assertNotEmpty(Category::findById($response->getPayload()->getId())->getEditedAt());
	}

	/**
	 * @dataProvider editCategoryProvider
	 */
	public function testExceptionWasThrownEditingCategory(string $exception, string $message, string $requestMethod, array $parameters, bool $generateCategory = false): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		if ($generateCategory) {
			$this->generateCategory($user, 'Pokemon');
		}

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function editCategoryProvider()
	{
		yield 'blank title' => [
			CategoryException::class,
			'Cannot edit Category: Missing title.',
			'PUT',
			[
				'body' => [
					'title' => ''
				],
				'header' => [1]
			],
			true
		];

		yield 'invalid ID' => [
			CategoryException::class,
			'Cannot edit Category: Category does not exist with ID 999.',
			'PUT',
			[
				'body' => [
					'title' => 'Pokemon'
				],
				'header' => [999]
			]
		];
	}

	public function testCategoryWasNotUpdatedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$category = $this->generateCategory($user);
		$newCategoryData = $this->generateCategoryData();
		$request = $this->createMockRequest([$category->getId()], 'PUT', [
			'title' => $newCategoryData['title'],
			'description' => $newCategoryData['description']
		]);
		$controller = new CategoryController($request, new JsonResponse());
		$response = $controller->doAction();

		$this->assertEquals('Cannot edit Category: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenCategoryUpdatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$category = $this->generateCategory($userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$newCategoryData = $this->generateCategoryData();

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$category->getId()], 'PUT', [
			'title' => $newCategoryData['title'],
			'description' => $newCategoryData['description']
		]);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CategoryException::class,
			'Cannot edit Category: You cannot edit a category that you did not create!',
			$controller,
			'doAction'
		));
	}

	public function testCategoryControllerCalledDestroy(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$category->getId()], 'DELETE');
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was deleted successfully!', $response->getMessage());
		$this->assertEquals($category->getId(), $response->getPayload()->getId());
		$this->assertEquals($category->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($category->getDescription(), $response->getPayload()->getDescription());
		$this->assertNull($category->getDeletedAt());
		$this->assertNotNull(Category::findById($category->getId())->getDeletedAt());
	}

	/**
	 * @dataProvider deleteCategoryProvider
	 */
	public function testExceptionWasThrownDestroyingCategory(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function deleteCategoryProvider()
	{
		yield 'invalid ID' => [
			CategoryException::class,
			'Cannot delete Category: Category does not exist with ID 999.',
			'DELETE',
			[
				'body' => [],
				'header' => [999]
			],
		];
	}

	public function testCategoryWasNotDestroyedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$category = $this->generateCategory($user);

		$request = $this->createMockRequest([$category->getId()], 'DELETE');
		$response = (new CategoryController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot delete Category: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenCategoryDestroyedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$category = $this->generateCategory($userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$category->getId()], 'DELETE');
		$controller = new CategoryController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CategoryException::class,
			'Cannot delete Category: You cannot delete a category that you did not create!',
			$controller,
			'doAction'
		));
	}
}
