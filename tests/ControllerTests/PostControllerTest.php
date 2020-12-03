<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\PostController;
use AssignmentSix\Exceptions\PostException;
use AssignmentSix\Models\Post;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class PostControllerTest extends ControllerTest
{
	public function testPostControllerCalledShow(): void
	{
		$post = $this->generatePost();
		$request = $this->createMockRequest([$post->getId()]);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was retrieved successfully!', $response->getMessage());
		$this->assertEquals($post->getId(), $response->getPayload()->getId());
		$this->assertEquals($post->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($post->getContent(), $response->getPayload()->getContent());
	}

	/**
	 * @dataProvider findPostProvider
	 */
	public function testExceptionWasThrownShowingPost(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function findPostProvider()
	{
		yield 'invalid ID' => [
			PostException::class,
			'Cannot find Post: Post does not exist with ID 1.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testPostControllerCalledNew(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory();
		$post = $this->generatePostData();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([], 'POST', [
			'userId' => $user->getId(),
			'categoryId' => $category->getId(),
			'title' => $post['title'],
			'type' => $post['type'],
			'content' => $post['content']
		]);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was created successfully!', $response->getMessage());
		$this->assertEquals($post['title'], $response->getPayload()->getTitle());
		$this->assertEquals($post['content'], $response->getPayload()->getContent());
		$this->assertNotEmpty($response->getPayload()->getId());
		$this->assertNotEmpty(Post::findById($response->getPayload()->getId())->getCreatedAt());
	}

	/**
	 * @dataProvider createPostProvider
	 */
	public function testExceptionWasThrownNewingPost(string $exception, string $message, string $requestMethod, array $parameters, bool $generatePost = false): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		if ($generatePost) {
			$this->generatePost(null, $user);
		}

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function createPostProvider()
	{
		yield 'string user ID' => [
			PostException::class,
			'Cannot create Post: User ID must be an integer.',
			'POST',
			[
				'body' => [
					'userId' => 'abc',
					'categoryId' => 1,
					'title' => 'Top 10 Pokemon',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			],
			true
		];

		yield 'string category ID' => [
			PostException::class,
			'Cannot create Post: Category ID must be an integer.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 'abc',
					'title' => 'Top 3 Pokemon!',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			],
			true
		];

		yield 'invalid category ID' => [
			PostException::class,
			'Cannot create Post: Category does not exist with ID 999.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 999,
					'title' => 'Top 3 Pokemon!',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			],
			true
		];

		yield 'blank title' => [
			PostException::class,
			'Cannot create Post: Missing title.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 1,
					'title' => '',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			]
		];

		yield 'blank type' => [
			PostException::class,
			"Cannot create Post: Type must be 'Text' or 'URL'.",
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 1,
					'title' => 'Top 3 Pokemon!',
					'type' => '',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			]
		];

		yield 'blank content' => [
			PostException::class,
			'Cannot create Post: Missing content.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 1,
					'title' => 'Top 3 Pokemon!',
					'type' => 'Text',
					'content' => ''
				],
				'header' => []
			]
		];
	}

	public function testPostWasNotCreatedWhenNotLoggedIn(): void
	{
		$postData = $this->generatePostData();
		$request = $this->createMockRequest([], 'POST', [
			'userId' => $postData['userId'],
			'categoryId' => $postData['categoryId'],
			'title' => $postData['title'],
			'type' => $postData['type'],
			'content' => $postData['content']
		]);
		$controller = new PostController($request, new JsonResponse());
		$response = $controller->doAction();

		$this->assertEquals('Cannot create Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenPostCreatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$postData = $this->generatePostData(null, $userA);

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([], 'POST', [
			'userId' => $postData['userId'],
			'categoryId' => $postData['categoryId'],
			'title' => $postData['title'],
			'type' => $postData['type'],
			'content' => $postData['content']
		]);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot create Post: You cannot create a post for someone else!',
			$controller,
			'doAction'
		));
	}

	public function testPostControllerCalledEdit(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost('Text', $user);
		$newPostData = $this->generatePostData('Text', $user);

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId()], 'PUT', [
			'content' => $newPostData['content']
		]);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was updated successfully!', $response->getMessage());
		$this->assertEquals($newPostData['content'], $response->getPayload()->getContent());
		$this->assertNotEquals($post->getContent(), $response->getPayload()->getContent());
		$this->assertNotEmpty(Post::findById($response->getPayload()->getId())->getEditedAt());
	}

	/**
	 * @dataProvider editPostProvider
	 */
	public function testExceptionWasThrownEditingPost(string $exception, string $message, string $requestMethod, array $parameters, bool $generatePost = false): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		if ($generatePost) {
			$this->generatePost('Text', $user);
		}

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function editPostProvider()
	{
		yield 'invalid ID' => [
			PostException::class,
			'Cannot edit Post: Post does not exist with ID 1.',
			'PUT',
			[
				'body' => [
					'content' => 'Pokemon are awesome!'
				],
				'header' => [1]
			]
		];

		yield 'blank content' => [
			PostException::class,
			'Cannot edit Post: Missing content.',
			'PUT',
			[
				'body' => [
					'content' => ''
				],
				'header' => [1]
			],
			true
		];
	}

	public function testPostWasNotUpdatedWhenNotLoggedIn(): void
	{
		$post = $this->generatePost('Text');
		$newPostData = $this->generatePostData('Text');
		$request = $this->createMockRequest([$post->getId()], 'PUT', [
			'content' => $newPostData['content']
		]);
		$controller = new PostController($request, new JsonResponse());
		$response = $controller->doAction();

		$this->assertEquals('Cannot edit Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenPostUpdatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$post = $this->generatePost('Text', $userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$newPostData = $this->generatePostData('Text', $userA);

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$post->getId()], 'PUT', [
			'content' => $newPostData['content']
		]);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot edit Post: You cannot edit a post that you did not create!',
			$controller,
			'doAction'
		));
	}

	public function testPostControllerCalledDestroy(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost(null, $user);

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId()], 'DELETE');
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was deleted successfully!', $response->getMessage());
		$this->assertEquals($post->getId(), $response->getPayload()->getId());
		$this->assertEquals($post->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($post->getContent(), $response->getPayload()->getContent());
		$this->assertNotNull(Post::findById($post->getId())->getDeletedAt());
	}

	/**
	 * @dataProvider deletePostProvider
	 */
	public function testExceptionWasThrownDestroyingPost(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function deletePostProvider()
	{
		yield 'invalid ID' => [
			PostException::class,
			'Cannot delete Post: Post does not exist with ID 999.',
			'DELETE',
			[
				'body' => [],
				'header' => [999]
			],
		];
	}

	public function testPostWasNotDeletedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost(null, $user);

		$request = $this->createMockRequest([$post->getId()], 'DELETE');
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot delete Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenPostDeletedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$post = $this->generatePost(null, $userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$post->getId()], 'DELETE');
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot delete Post: You cannot delete a post that you did not create!',
			$controller,
			'doAction'
		));
	}
}
