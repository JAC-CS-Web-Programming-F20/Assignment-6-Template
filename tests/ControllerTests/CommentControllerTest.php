<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\CommentController;
use AssignmentSix\Exceptions\CommentException;
use AssignmentSix\Models\Comment;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class CommentControllerTest extends ControllerTest
{
	public function testCommentControllerCalledShow(): void
	{
		$comment = $this->generateComment();
		$request = $this->createMockRequest([$comment->getId()]);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was retrieved successfully!', $response->getMessage());
		$this->assertEquals($comment->getUser()->getId(), $response->getPayload()->getUser()->getId());
		$this->assertEquals($comment->getPost()->getId(), $response->getPayload()->getPost()->getId());
		$this->assertEquals($comment->getContent(), $response->getPayload()->getContent());
	}

	/**
	 * @dataProvider findCommentProvider
	 */
	public function testExceptionWasThrownShowingComment(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function findCommentProvider()
	{
		yield 'invalid ID' => [
			CommentException::class,
			'Cannot find Comment: Comment does not exist with ID 1.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testCommentControllerCalledNew(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$comment = $this->generateCommentData($user);

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([], 'POST', [
			'postId' => $comment['postId'],
			'userId' => $comment['userId'],
			'content' => $comment['content'],
			'replyId' => $comment['replyId']
		]);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was created successfully!', $response->getMessage());
		$this->assertEquals($comment['userId'], $response->getPayload()->getUser()->getId());
		$this->assertEquals($comment['content'], $response->getPayload()->getContent());
		$this->assertNotEmpty($response->getPayload()->getId());
		$this->assertNotEmpty(Comment::findById($response->getPayload()->getId())->getCreatedAt());
	}

	/**
	 * @dataProvider createCommentProvider
	 */
	public function testExceptionWasThrownNewingComment(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function createCommentProvider()
	{
		yield 'blank content' => [
			CommentException::class,
			'Cannot create Comment: Missing content.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'postId' => 1,
					'content' => '',
					'replyId' => null
				],
				'header' => []
			]
		];

		yield 'non-existant post' => [
			CommentException::class,
			'Cannot create Comment: Post does not exist with ID 999.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'postId' => 999,
					'content' => 'The best Pokemon community!',
					'replyId' => null
				],
				'header' => []
			]
		];
	}

	public function testCommentWasNotCreatedWhenNotLoggedIn(): void
	{
		$commentData = $this->generateCommentData();
		$request = $this->createMockRequest([], 'POST', [
			'postId' => $commentData['postId'],
			'userId' => $commentData['userId'],
			'content' => $commentData['content'],
			'replyId' => $commentData['replyId']
		]);
		$controller = new CommentController($request, new JsonResponse());
		$response = $controller->doAction();

		$this->assertEquals('Cannot create Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenCommentCreatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$commentData = $this->generateCommentData($userA);

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([], 'POST', [
			'postId' => $commentData['postId'],
			'userId' => $commentData['userId'],
			'content' => $commentData['content'],
			'replyId' => $commentData['replyId']
		]);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot create Comment: You cannot create a comment for someone else!',
			$controller,
			'doAction'
		));
	}

	public function testCommentControllerCalledEdit(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$comment = $this->generateComment($user);
		$newCommentData = $this->generateCommentData($user);

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId()], 'PUT', [
			'content' => $newCommentData['content']
		]);

		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was updated successfully!', $response->getMessage());
		$this->assertEquals($newCommentData['content'], $response->getPayload()->getContent());
		$this->assertNotEquals($comment->getContent(), $response->getPayload()->getUser()->getId());
		$this->assertNotEmpty(Comment::findById($response->getPayload()->getId())->getEditedAt());
	}

	/**
	 * @dataProvider editCommentProvider
	 */
	public function testExceptionWasThrownEditingComment(string $exception, string $message, string $requestMethod, array $parameters, bool $generateComment = false): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		if ($generateComment) {
			$this->generateComment($user);
		}

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function editCommentProvider()
	{
		yield 'invalid ID' => [
			CommentException::class,
			'Cannot edit Comment: Comment does not exist with ID 1.',
			'PUT',
			[
				'body' => [
					'content' => 'Pokemon are awesome!'
				],
				'header' => [1]
			]
		];

		yield 'blank content' => [
			CommentException::class,
			'Cannot edit Comment: Missing content.',
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

	public function testCommentWasNotUpdatedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$newCommentData = $this->generateCommentData();
		$request = $this->createMockRequest([$comment->getId()], 'PUT', [
			'postId' => $newCommentData['postId'],
			'userId' => $newCommentData['userId'],
			'content' => $newCommentData['content'],
			'replyId' => $newCommentData['replyId']
		]);
		$controller = new CommentController($request, new JsonResponse());
		$response = $controller->doAction();

		$this->assertEquals('Cannot edit Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenCommentUpdatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$comment = $this->generateComment($userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$newCommentData = $this->generateCommentData($userA);

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$comment->getId()], 'PUT', [
			'postId' => $newCommentData['postId'],
			'userId' => $newCommentData['userId'],
			'content' => $newCommentData['content'],
			'replyId' => $newCommentData['replyId']
		]);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot edit Comment: You cannot edit a comment that you did not create!',
			$controller,
			'doAction'
		));
	}

	public function testCommentControllerCalledDestroy(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$comment = $this->generateComment($user);

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId()], 'DELETE');
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was deleted successfully!', $response->getMessage());
		$this->assertEquals($comment->getUser()->getId(), $response->getPayload()->getUser()->getId());
		$this->assertEquals($comment->getPost()->getId(), $response->getPayload()->getPost()->getId());
		$this->assertEquals($comment->getContent(), $response->getPayload()->getContent());
		$this->assertNotNull(Comment::findById($comment->getId())->getDeletedAt());
	}

	/**
	 * @dataProvider deleteCommentProvider
	 */
	public function testExceptionWasThrownDestroyingComment(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest($parameters['header'], $requestMethod, $parameters['body']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown($exception, $message, $controller, 'doAction'));
	}

	public function deleteCommentProvider()
	{
		yield 'invalid ID' => [
			CommentException::class,
			'Cannot delete Comment: Comment does not exist with ID 999.',
			'DELETE',
			[
				'body' => [],
				'header' => [999]
			],
		];
	}

	public function testCommentWasNotDeletedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment($user);

		$request = $this->createMockRequest([$comment->getId()], 'DELETE');
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot delete Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testExceptionWasThrownWhenCommentDeletedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$comment = $this->generateComment($userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->logIn($userDataB['email'], $userDataB['password']);

		$request = $this->createMockRequest([$comment->getId()], 'DELETE');
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot delete Comment: You cannot delete a comment that you did not create!',
			$controller,
			'doAction'
		));
	}
}
