<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\PostController;
use AssignmentSix\Controllers\UserController;
use AssignmentSix\Exceptions\PostException;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class PostBookmarkControllerTest extends ControllerTest
{
	public function testPostControllerCalledBookmark(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$postId, 'bookmark']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('bookmark', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was bookmarked successfully!', $response->getMessage());

		$request = $this->createMockRequest([$user->getId(), 'postbookmarks']);
		$controller = new UserController($request, new JsonResponse());
		$response = $controller->doAction();
		$bookmarkedPosts = $response->getPayload();

		$this->assertEquals(1, sizeOf($bookmarkedPosts));
		$this->assertEquals($postId, $bookmarkedPosts[0]->getId());
	}

	public function testPostControllerCalledUnbookmark(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$post->bookmark($user->getId());

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'unbookmark']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('unbookmark', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was unbookmarked successfully!', $response->getMessage());
	}

	public function testPostWasNotBookmarkedWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();

		$request = $this->createMockRequest([$post->getId(), 'bookmark']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot bookmark Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testPostWasNotUnbookmarkedWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();

		$request = $this->createMockRequest([$post->getId(), 'unbookmark']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot unbookmark Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testPostWasBookmarkedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$postId = $this->generatePost()->getId();

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$users[] = $this->generateUser(...array_values($userData));
			$this->logIn($userData['email'], $userData['password']);
			$postIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$request = $this->createMockRequest([$postId, 'bookmark']);
				(new PostController($request, new JsonResponse()))->doAction();
				$postIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$request = $this->createMockRequest([$postId, 'unbookmark']);
					(new PostController($request, new JsonResponse()))->doAction();
					$postIsBookmarked[$i] = false;
				}
			}
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$request = $this->createMockRequest([$users[$i]->getId(), 'postbookmarks']);
			$controller = new UserController($request, new JsonResponse());
			$response = $controller->doAction();
			$bookmarkedPosts = $response->getPayload();

			if ($postIsBookmarked[$i]) {
				$this->assertEquals(1, sizeOf($bookmarkedPosts));
				$this->assertEquals($postId, $bookmarkedPosts[0]->getId());
			} else {
				$this->assertEmpty($bookmarkedPosts);
			}
		}
	}

	public function testExceptionWasThrownWhenBookmarkingPostTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'bookmark']);
		(new PostController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$post->getId(), 'bookmark']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot bookmark Post: Post has already been bookmarked.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnbookmarkingPostTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'unbookmark']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot unbookmark Post: Post has not been bookmarked.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnbookmarkingBeforeBookmarking(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'bookmark']);
		(new PostController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$post->getId(), 'unbookmark']);
		(new PostController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$post->getId(), 'unbookmark']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot unbookmark Post: Post has not been bookmarked.',
			$controller,
			'doAction'
		));
	}
}
