<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\PostController;
use AssignmentSix\Exceptions\PostException;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class PostVoteControllerTest extends ControllerTest
{
	public function testPostControllerCalledUpVote(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('upVote', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was up voted successfully!', $response->getMessage());
		$this->assertEquals($post->getId(), $response->getPayload()->getId());
		$this->assertEquals($post->getUpvotes() + 1, $response->getPayload()->getUpvotes());
	}

	public function testPostControllerCalledDownVote(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'downvote']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('downVote', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was down voted successfully!', $response->getMessage());
		$this->assertEquals($post->getId(), $response->getPayload()->getId());
		$this->assertEquals($post->getDownvotes() + 1, $response->getPayload()->getDownvotes());
	}

	public function testPostControllerCalledUnvote(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'unvote']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('unvote', $controller->getAction());
	}

	public function testPostWasNotUpVotedWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot up vote Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testPostWasNotDownVotedWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();

		$request = $this->createMockRequest([$post->getId(), 'downvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot down vote Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testPostWasNotUnvotedWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();

		$request = $this->createMockRequest([$post->getId(), 'unvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot unvote Post: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testPostWasUpVotedThenDownVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(1, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$post->getId(), 'downvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(1, $response->getPayload()->getDownvotes());
	}

	public function testPostWasDownVotedThenUpVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'downvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(1, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(1, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());
	}

	public function testPostWasUpVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(1, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$post->getId(), 'unvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());
	}

	public function testPostWasDownVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'downvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(1, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$post->getId(), 'unvote']);
		$response = (new PostController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());
	}

	public function testPostWasVotedByMultipleUsers(): void
	{
		$post = $this->generatePost();
		$numberOfUsers = rand(10, 20);
		$upvotes = 0;
		$downvotes = 0;

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$this->generateUser(...array_values($userData));
			$this->logIn($userData['email'], $userData['password']);

			if (rand(0, 1) === 0) {
				$request = $this->createMockRequest([$post->getId(), 'upvote']);
				$response = (new PostController($request, new JsonResponse()))->doAction();
				$upvotes++;

				if (rand(0, 1) === 0) {
					$request = $this->createMockRequest([$post->getId(), 'unvote']);
					$response = (new PostController($request, new JsonResponse()))->doAction();
					$upvotes--;
				}
			} else {
				$request = $this->createMockRequest([$post->getId(), 'downvote']);
				$response = (new PostController($request, new JsonResponse()))->doAction();
				$downvotes++;

				if (rand(0, 1) === 0) {
					$request = $this->createMockRequest([$post->getId(), 'unvote']);
					$response = (new PostController($request, new JsonResponse()))->doAction();
					$downvotes--;
				}
			}
		}

		$retrievedPost = $response->getPayload();

		$this->assertEquals($upvotes, $retrievedPost->getUpvotes());
		$this->assertEquals($downvotes, $retrievedPost->getDownvotes());
	}

	public function testExceptionWasThrownWhenUpVotingPostTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		(new PostController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot up vote Post: Post has already been up voted.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenDownVotingPostTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'downvote']);
		(new PostController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$post->getId(), 'downvote']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot down vote Post: Post has already been down voted.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnvotingBeforeVoting(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'unvote']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot unvote Post: Post must first be up or down voted.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnvotingTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$post->getId(), 'upvote']);
		(new PostController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$post->getId(), 'unvote']);
		(new PostController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$post->getId(), 'unvote']);
		$controller = new PostController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			PostException::class,
			'Cannot unvote Post: Post must first be up or down voted.',
			$controller,
			'doAction'
		));
	}
}
