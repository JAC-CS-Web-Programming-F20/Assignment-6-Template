<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\RouterTests\RouterTest;
use HttpStatusCode;

final class CommentVoteRouterTest extends RouterTest
{
	public function testCommentWasUpVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedComment = $this->getResponse("comment/$commentId/upvote");

		$this->assertEquals(HttpStatusCode::OK, $upVotedComment['status']);
		$this->assertEquals('Comment was up voted successfully!', $upVotedComment['message']);
		$this->assertEquals($comment->getUpvotes() + 1, $upVotedComment['payload']['upvotes']);
	}

	public function testCommentWasDownVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$downVotedComment = $this->getResponse("comment/$commentId/downvote");

		$this->assertEquals(HttpStatusCode::OK, $downVotedComment['status']);
		$this->assertEquals('Comment was down voted successfully!', $downVotedComment['message']);
		$this->assertEquals($comment->getDownvotes() + 1, $downVotedComment['payload']['downvotes']);
	}

	public function testCommentWasNotUpVotedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$upVotedComment = $this->getResponse("comment/$commentId/upvote");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $upVotedComment['status']);
		$this->assertEquals('Cannot up vote Comment: You must be logged in.', $upVotedComment['message']);
		$this->assertEmpty($upVotedComment['payload']);
	}

	public function testCommentWasNotDownVotedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$downVotedComment = $this->getResponse("comment/$commentId/downvote");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $downVotedComment['status']);
		$this->assertEquals('Cannot down vote Comment: You must be logged in.', $downVotedComment['message']);
		$this->assertEmpty($downVotedComment['payload']);
	}

	public function testCommentWasNotUnvotedWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$unvotedComment = $this->getResponse("comment/$commentId/unvote");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $unvotedComment['status']);
		$this->assertEquals('Cannot unvote Comment: You must be logged in.', $unvotedComment['message']);
		$this->assertEmpty($unvotedComment['payload']);
	}

	public function testCommentWasUpVotedThenDownVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedComment = $this->getResponse("comment/$commentId/upvote");

		$this->assertEquals(1, $upVotedComment['payload']['upvotes']);
		$this->assertEquals(0, $upVotedComment['payload']['downvotes']);

		$downVotedComment = $this->getResponse("comment/$commentId/downvote");

		$this->assertEquals(0, $downVotedComment['payload']['upvotes']);
		$this->assertEquals(1, $downVotedComment['payload']['downvotes']);
	}

	public function testCommentWasDownVotedThenUpVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedComment = $this->getResponse("comment/$commentId/downvote");

		$this->assertEquals(0, $upVotedComment['payload']['upvotes']);
		$this->assertEquals(1, $upVotedComment['payload']['downvotes']);

		$downVotedComment = $this->getResponse("comment/$commentId/upvote");

		$this->assertEquals(1, $downVotedComment['payload']['upvotes']);
		$this->assertEquals(0, $downVotedComment['payload']['downvotes']);
	}

	public function testCommentWasUpVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedComment = $this->getResponse("comment/$commentId/upvote");

		$this->assertEquals(1, $upVotedComment['payload']['upvotes']);
		$this->assertEquals(0, $upVotedComment['payload']['downvotes']);

		$downVotedComment = $this->getResponse("comment/$commentId/unvote");

		$this->assertEquals(0, $downVotedComment['payload']['upvotes']);
		$this->assertEquals(0, $downVotedComment['payload']['downvotes']);
	}

	public function testCommentWasDownVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedComment = $this->getResponse("comment/$commentId/downvote");

		$this->assertEquals(0, $upVotedComment['payload']['upvotes']);
		$this->assertEquals(1, $upVotedComment['payload']['downvotes']);

		$downVotedComment = $this->getResponse("comment/$commentId/unvote");

		$this->assertEquals(0, $downVotedComment['payload']['upvotes']);
		$this->assertEquals(0, $downVotedComment['payload']['downvotes']);
	}

	public function testCommentWasVotedByMultipleUsers(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$numberOfUsers = rand(10, 20);
		$upvotes = 0;
		$downvotes = 0;

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$this->generateUser(...array_values($userData));
			$this->getResponse(
				'auth/login',
				'POST',
				$userData
			);

			if (rand(0, 1) === 0) {
				$this->getResponse("comment/$commentId/upvote");
				$upvotes++;

				if (rand(0, 1) === 0) {
					$this->getResponse("comment/$commentId/unvote");
					$upvotes--;
				}
			} else {
				$this->getResponse("comment/$commentId/downvote");
				$downvotes++;

				if (rand(0, 1) === 0) {
					$this->getResponse("comment/$commentId/unvote");
					$downvotes--;
				}
			}
		}

		$retrievedComment = $this->getResponse("comment/$commentId");

		$this->assertEquals($upvotes, $retrievedComment['payload']['upvotes']);
		$this->assertEquals($downvotes, $retrievedComment['payload']['downvotes']);
	}

	public function testCommentWasNotUpVotedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse("comment/$commentId/upvote");
		$upVotedComment = $this->getResponse("comment/$commentId/upvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $upVotedComment['status']);
		$this->assertEquals('Cannot up vote Comment: Comment has already been up voted.', $upVotedComment['message']);
		$this->assertEmpty($upVotedComment['payload']);
	}

	public function testCommentWasNotDownVotedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse("comment/$commentId/downvote");
		$downVotedComment = $this->getResponse("comment/$commentId/downvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $downVotedComment['status']);
		$this->assertEquals('Cannot down vote Comment: Comment has already been down voted.', $downVotedComment['message']);
		$this->assertEmpty($downVotedComment['payload']);
	}

	public function testCommentWasNotUnvotedBeforeVoting(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$unvotedComment = $this->getResponse("comment/$commentId/unvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unvotedComment['status']);
		$this->assertEquals('Cannot unvote Comment: Comment must first be up or down voted.', $unvotedComment['message']);
		$this->assertEmpty($unvotedComment['payload']);
	}

	public function testCommentWasNotUnvotedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->getResponse("comment/$commentId/upvote");
		$this->getResponse("comment/$commentId/unvote");
		$unvotedComment = $this->getResponse("comment/$commentId/unvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unvotedComment['status']);
		$this->assertEquals('Cannot unvote Comment: Comment must first be up or down voted.', $unvotedComment['message']);
		$this->assertEmpty($unvotedComment['payload']);
	}
}
