<?php

namespace AssignmentSixTests\ModelTests;

use AssignmentSix\Exceptions\PostException;
use AssignmentSix\Models\Post;
use AssignmentSixTests\ModelTests\ModelTest;

final class PostVoteTest extends ModelTest
{
	public function testPostWasUpVoted(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertTrue($post->upVote($user->getId()));

		$retrievedPost = Post::findById($post->getId());

		$this->assertEquals(1, $retrievedPost->getUpvotes());
		$this->assertEquals(0, $retrievedPost->getDownvotes());
	}

	public function testPostWasDownVoted(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertTrue($post->downVote($user->getId()));

		$retrievedPost = Post::findById($post->getId());

		$this->assertEquals(0, $retrievedPost->getUpvotes());
		$this->assertEquals(1, $retrievedPost->getDownvotes());
	}

	public function testPostWasUpVotedThenDownVoted(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertTrue($post->upVote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(1, $post->getUpvotes());
		$this->assertEquals(0, $post->getDownvotes());

		$this->assertTrue($post->downVote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(0, $post->getUpvotes());
		$this->assertEquals(1, $post->getDownvotes());
	}

	public function testPostWasDownVotedThenUpVoted(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertTrue($post->downVote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(0, $post->getUpvotes());
		$this->assertEquals(1, $post->getDownvotes());

		$this->assertTrue($post->upVote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(1, $post->getUpvotes());
		$this->assertEquals(0, $post->getDownvotes());
	}

	public function testPostWasUpVotedThenUnvoted(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertTrue($post->upVote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(1, $post->getUpvotes());
		$this->assertEquals(0, $post->getDownvotes());

		$this->assertTrue($post->unvote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(0, $post->getUpvotes());
		$this->assertEquals(0, $post->getDownvotes());
	}

	public function testPostWasDownVotedThenUnvoted(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertTrue($post->downVote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(0, $post->getUpvotes());
		$this->assertEquals(1, $post->getDownvotes());

		$this->assertTrue($post->unvote($user->getId()));

		$post = Post::findById($post->getId());

		$this->assertEquals(0, $post->getUpvotes());
		$this->assertEquals(0, $post->getDownvotes());
	}

	public function testPostWasVotedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$post = $this->generatePost();
		$upvotes = 0;
		$downvotes = 0;

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$user = $this->generateUser();

			if (rand(0, 1) === 0) {
				$post->upVote($user->getId());
				$upvotes++;

				if (rand(0, 1) === 0) {
					$post->unvote($user->getId());
					$upvotes--;
				}
			} else {
				$post->downVote($user->getId());
				$downvotes++;

				if (rand(0, 1) === 0) {
					$post->unvote($user->getId());
					$downvotes--;
				}
			}
		}

		$retrievedPost = Post::findById($post->getId());

		$this->assertEquals($upvotes, $retrievedPost->getUpvotes());
		$this->assertEquals($downvotes, $retrievedPost->getDownvotes());
	}

	public function testExceptionWasThrownWhenUpVotingPostTwice(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot up vote Post: Post has already been up voted.");

		$user = $this->generateUser();
		$post = $this->generatePost();

		$post->upVote($user->getId());
		$post->upVote($user->getId());
	}

	public function testExceptionWasThrownWhenDownVotingPostTwice(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot down vote Post: Post has already been down voted.");

		$user = $this->generateUser();
		$post = $this->generatePost();

		$post->downVote($user->getId());
		$post->downVote($user->getId());
	}

	public function testExceptionWasThrownWhenUnvotingBeforeVoting(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot unvote Post: Post must first be up or down voted.");

		$user = $this->generateUser();
		$post = $this->generatePost();

		$post->unVote($user->getId());
	}

	public function testExceptionWasThrownWhenUnvotingTwice(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot unvote Post: Post must first be up or down voted.");

		$user = $this->generateUser();
		$post = $this->generatePost();

		$post->upVote($user->getId());
		$post->unvote($user->getId());
		$post->unvote($user->getId());
	}
}
