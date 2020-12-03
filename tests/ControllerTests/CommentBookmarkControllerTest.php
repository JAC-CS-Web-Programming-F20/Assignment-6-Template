<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\CommentController;
use AssignmentSix\Controllers\UserController;
use AssignmentSix\Exceptions\CommentException;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class CommentBookmarkControllerTest extends ControllerTest
{
	public function testCommentControllerCalledBookmark(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$commentId = $this->generateComment()->getId();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$commentId, 'bookmark']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('bookmark', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was bookmarked successfully!', $response->getMessage());

		$request = $this->createMockRequest([$user->getId(), 'commentbookmarks']);
		$controller = new UserController($request, new JsonResponse());
		$response = $controller->doAction();
		$bookmarkedComments = $response->getPayload();

		$this->assertEquals(1, sizeOf($bookmarkedComments));
		$this->assertEquals($commentId, $bookmarkedComments[0]->getId());
	}

	public function testCommentControllerCalledUnbookmark(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$comment->bookmark($user->getId());

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'unbookmark']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('unbookmark', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was unbookmarked successfully!', $response->getMessage());
	}

	public function testCommentWasNotBookmarkedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();

		$request = $this->createMockRequest([$comment->getId(), 'bookmark']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot bookmark Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testCommentWasNotUnbookmarkedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();

		$request = $this->createMockRequest([$comment->getId(), 'unbookmark']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot unbookmark Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testCommentWasBookmarkedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$commentId = $this->generateComment()->getId();

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$users[] = $this->generateUser(...array_values($userData));
			$this->logIn($userData['email'], $userData['password']);
			$commentIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$request = $this->createMockRequest([$commentId, 'bookmark']);
				(new CommentController($request, new JsonResponse()))->doAction();
				$commentIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$request = $this->createMockRequest([$commentId, 'unbookmark']);
					(new CommentController($request, new JsonResponse()))->doAction();
					$commentIsBookmarked[$i] = false;
				}
			}
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$request = $this->createMockRequest([$users[$i]->getId(), 'commentbookmarks']);
			$controller = new UserController($request, new JsonResponse());
			$response = $controller->doAction();
			$bookmarkedComments = $response->getPayload();

			if ($commentIsBookmarked[$i]) {
				$this->assertEquals(1, sizeOf($bookmarkedComments));
				$this->assertEquals($commentId, $bookmarkedComments[0]->getId());
			} else {
				$this->assertEmpty($bookmarkedComments);
			}
		}
	}

	public function testExceptionWasThrownWhenBookmarkingCommentTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'bookmark']);
		(new CommentController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$comment->getId(), 'bookmark']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot bookmark Comment: Comment has already been bookmarked.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnbookmarkingCommentTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'unbookmark']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot unbookmark Comment: Comment has not been bookmarked.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnbookmarkingBeforeBookmarking(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'bookmark']);
		(new CommentController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$comment->getId(), 'unbookmark']);
		(new CommentController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$comment->getId(), 'unbookmark']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot unbookmark Comment: Comment has not been bookmarked.',
			$controller,
			'doAction'
		));
	}
}
