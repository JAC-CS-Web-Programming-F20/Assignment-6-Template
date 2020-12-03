<?php

namespace AssignmentSixTests\ModelTests;

use AssignmentSix\Exceptions\CommentException;
use AssignmentSix\Models\Comment;
use AssignmentSixTests\ModelTests\ModelTest;

final class CommentVoteTest extends ModelTest
{
	public function testCommentWasUpVoted(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertTrue($comment->upVote($user->getId()));

		$retrievedComment = Comment::findById($comment->getId());

		$this->assertEquals(1, $retrievedComment->getUpvotes());
		$this->assertEquals(0, $retrievedComment->getDownvotes());
	}

	public function testCommentWasDownVoted(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertTrue($comment->downVote($user->getId()));

		$retrievedComment = Comment::findById($comment->getId());

		$this->assertEquals(0, $retrievedComment->getUpvotes());
		$this->assertEquals(1, $retrievedComment->getDownvotes());
	}

	public function testCommentWasUpVotedThenDownVoted(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertTrue($comment->upVote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(1, $comment->getUpvotes());
		$this->assertEquals(0, $comment->getDownvotes());

		$this->assertTrue($comment->downVote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(0, $comment->getUpvotes());
		$this->assertEquals(1, $comment->getDownvotes());
	}

	public function testCommentWasDownVotedThenUpVoted(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertTrue($comment->downVote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(0, $comment->getUpvotes());
		$this->assertEquals(1, $comment->getDownvotes());

		$this->assertTrue($comment->upVote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(1, $comment->getUpvotes());
		$this->assertEquals(0, $comment->getDownvotes());
	}

	public function testCommentWasUpVotedThenUnvoted(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertTrue($comment->upVote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(1, $comment->getUpvotes());
		$this->assertEquals(0, $comment->getDownvotes());

		$this->assertTrue($comment->unvote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(0, $comment->getUpvotes());
		$this->assertEquals(0, $comment->getDownvotes());
	}

	public function testCommentWasDownVotedThenUnvoted(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertTrue($comment->downVote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(0, $comment->getUpvotes());
		$this->assertEquals(1, $comment->getDownvotes());

		$this->assertTrue($comment->unvote($user->getId()));

		$comment = Comment::findById($comment->getId());

		$this->assertEquals(0, $comment->getUpvotes());
		$this->assertEquals(0, $comment->getDownvotes());
	}

	public function testCommentWasVotedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$comment = $this->generateComment();
		$upvotes = 0;
		$downvotes = 0;

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$user = $this->generateUser();

			if (rand(0, 1) === 0) {
				$comment->upVote($user->getId());
				$upvotes++;

				if (rand(0, 1) === 0) {
					$comment->unvote($user->getId());
					$upvotes--;
				}
			} else {
				$comment->downVote($user->getId());
				$downvotes++;

				if (rand(0, 1) === 0) {
					$comment->unvote($user->getId());
					$downvotes--;
				}
			}
		}

		$retrievedComment = Comment::findById($comment->getId());

		$this->assertEquals($upvotes, $retrievedComment->getUpvotes());
		$this->assertEquals($downvotes, $retrievedComment->getDownvotes());
	}

	public function testExceptionWasThrownWhenUpVotingCommentTwice(): void
	{
		$this->expectException(CommentException::class);
		$this->expectExceptionMessage("Cannot up vote Comment: Comment has already been up voted.");

		$user = $this->generateUser();
		$comment = $this->generateComment();

		$comment->upVote($user->getId());
		$comment->upVote($user->getId());
	}

	public function testExceptionWasThrownWhenDownVotingCommentTwice(): void
	{
		$this->expectException(CommentException::class);
		$this->expectExceptionMessage("Cannot down vote Comment: Comment has already been down voted.");

		$user = $this->generateUser();
		$comment = $this->generateComment();

		$comment->downVote($user->getId());
		$comment->downVote($user->getId());
	}

	public function testExceptionWasThrownWhenUnvotingBeforeVoting(): void
	{
		$this->expectException(CommentException::class);
		$this->expectExceptionMessage("Cannot unvote Comment: Comment must first be up or down voted.");

		$user = $this->generateUser();
		$comment = $this->generateComment();

		$comment->unVote($user->getId());
	}

	public function testExceptionWasThrownWhenUnvotingTwice(): void
	{
		$this->expectException(CommentException::class);
		$this->expectExceptionMessage("Cannot unvote Comment: Comment must first be up or down voted.");

		$user = $this->generateUser();
		$comment = $this->generateComment();

		$comment->upVote($user->getId());
		$comment->unvote($user->getId());
		$comment->unvote($user->getId());
	}
}
