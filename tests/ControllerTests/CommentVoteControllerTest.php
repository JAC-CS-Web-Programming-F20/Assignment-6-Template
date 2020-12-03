<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\CommentController;
use AssignmentSix\Exceptions\CommentException;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Response;
use AssignmentSixTests\ControllerTests\ControllerTest;

/**
 * There are a lot of angry red squigglies in this file. This is due to the
 * intellisense getting confused about types. Don't worry about them too much.
 * Everything works, trust me!
 */
final class CommentVoteControllerTest extends ControllerTest
{
	public function testCommentControllerCalledUpVote(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('upVote', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was up voted successfully!', $response->getMessage());
		$this->assertEquals($comment->getId(), $response->getPayload()->getId());
		$this->assertEquals($comment->getUpvotes() + 1, $response->getPayload()->getUpvotes());
	}

	public function testCommentControllerCalledDownVote(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'downvote']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('downVote', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was down voted successfully!', $response->getMessage());
		$this->assertEquals($comment->getId(), $response->getPayload()->getId());
		$this->assertEquals($comment->getDownvotes() + 1, $response->getPayload()->getDownvotes());
	}

	public function testCommentControllerCalledUnvote(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'unvote']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('unvote', $controller->getAction());
	}

	public function testCommentWasNotUpVotedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot up vote Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testCommentWasNotDownVotedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();

		$request = $this->createMockRequest([$comment->getId(), 'downvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot down vote Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testCommentWasNotUnvotedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();

		$request = $this->createMockRequest([$comment->getId(), 'unvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals('Cannot unvote Comment: You must be logged in.', $response->getMessage());
		$this->assertEmpty($response->getPayload());
	}

	public function testCommentWasUpVotedThenDownVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(1, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$comment->getId(), 'downvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(1, $response->getPayload()->getDownvotes());
	}

	public function testCommentWasDownVotedThenUpVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'downvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(1, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(1, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());
	}

	public function testCommentWasUpVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(1, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$comment->getId(), 'unvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());
	}

	public function testCommentWasDownVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'downvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(1, $response->getPayload()->getDownvotes());

		$request = $this->createMockRequest([$comment->getId(), 'unvote']);
		$response = (new CommentController($request, new JsonResponse()))->doAction();

		$this->assertEquals(0, $response->getPayload()->getUpvotes());
		$this->assertEquals(0, $response->getPayload()->getDownvotes());
	}

	public function testCommentWasVotedByMultipleUsers(): void
	{
		$comment = $this->generateComment();
		$numberOfUsers = rand(10, 20);
		$upvotes = 0;
		$downvotes = 0;

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$this->generateUser(...array_values($userData));
			$this->logIn($userData['email'], $userData['password']);

			if (rand(0, 1) === 0) {
				$request = $this->createMockRequest([$comment->getId(), 'upvote']);
				$response = (new CommentController($request, new JsonResponse()))->doAction();
				$upvotes++;

				if (rand(0, 1) === 0) {
					$request = $this->createMockRequest([$comment->getId(), 'unvote']);
					$response = (new CommentController($request, new JsonResponse()))->doAction();
					$upvotes--;
				}
			} else {
				$request = $this->createMockRequest([$comment->getId(), 'downvote']);
				$response = (new CommentController($request, new JsonResponse()))->doAction();
				$downvotes++;

				if (rand(0, 1) === 0) {
					$request = $this->createMockRequest([$comment->getId(), 'unvote']);
					$response = (new CommentController($request, new JsonResponse()))->doAction();
					$downvotes--;
				}
			}
		}

		$retrievedComment = $response->getPayload();

		$this->assertEquals($upvotes, $retrievedComment->getUpvotes());
		$this->assertEquals($downvotes, $retrievedComment->getDownvotes());
	}

	public function testExceptionWasThrownWhenUpVotingCommentTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		(new CommentController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot up vote Comment: Comment has already been up voted.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenDownVotingCommentTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'downvote']);
		(new CommentController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$comment->getId(), 'downvote']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot down vote Comment: Comment has already been down voted.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnvotingBeforeVoting(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'unvote']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot unvote Comment: Comment must first be up or down voted.',
			$controller,
			'doAction'
		));
	}

	public function testExceptionWasThrownWhenUnvotingTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->logIn($userData['email'], $userData['password']);

		$request = $this->createMockRequest([$comment->getId(), 'upvote']);
		(new CommentController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$comment->getId(), 'unvote']);
		(new CommentController($request, new JsonResponse()))->doAction();

		$request = $this->createMockRequest([$comment->getId(), 'unvote']);
		$controller = new CommentController($request, new JsonResponse());

		$this->assertTrue($this->wasExceptionThrown(
			CommentException::class,
			'Cannot unvote Comment: Comment must first be up or down voted.',
			$controller,
			'doAction'
		));
	}
}
