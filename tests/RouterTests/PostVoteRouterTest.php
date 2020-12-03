<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\RouterTests\RouterTest;
use HttpStatusCode;

final class PostVoteRouterTest extends RouterTest
{
	public function testPostWasUpVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedPost = $this->getResponse("post/$postId/upvote");

		$this->assertEquals(HttpStatusCode::OK, $upVotedPost['status']);
		$this->assertEquals('Post was up voted successfully!', $upVotedPost['message']);
		$this->assertEquals($post->getUpvotes() + 1, $upVotedPost['payload']['upvotes']);
	}

	public function testPostWasDownVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$downVotedPost = $this->getResponse("post/$postId/downvote");

		$this->assertEquals(HttpStatusCode::OK, $downVotedPost['status']);
		$this->assertEquals('Post was down voted successfully!', $downVotedPost['message']);
		$this->assertEquals($post->getDownvotes() + 1, $downVotedPost['payload']['downvotes']);
	}

	public function testPostWasNotUpVotedWhenNotLoggedIn(): void
	{
		$postId = $this->generatePost()->getId();
		$upVotedPost = $this->getResponse("post/$postId/upvote");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $upVotedPost['status']);
		$this->assertEquals('Cannot up vote Post: You must be logged in.', $upVotedPost['message']);
		$this->assertEmpty($upVotedPost['payload']);
	}

	public function testPostWasNotDownVotedWhenNotLoggedIn(): void
	{
		$postId = $this->generatePost()->getId();
		$downVotedPost = $this->getResponse("post/$postId/downvote");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $downVotedPost['status']);
		$this->assertEquals('Cannot down vote Post: You must be logged in.', $downVotedPost['message']);
		$this->assertEmpty($downVotedPost['payload']);
	}

	public function testPostWasNotUnvotedWhenNotLoggedIn(): void
	{
		$postId = $this->generatePost()->getId();
		$unvotedPost = $this->getResponse("post/$postId/unvote");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $unvotedPost['status']);
		$this->assertEquals('Cannot unvote Post: You must be logged in.', $unvotedPost['message']);
		$this->assertEmpty($unvotedPost['payload']);
	}

	public function testPostWasUpVotedThenDownVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedPost = $this->getResponse("post/$postId/upvote");

		$this->assertEquals(1, $upVotedPost['payload']['upvotes']);
		$this->assertEquals(0, $upVotedPost['payload']['downvotes']);

		$downVotedPost = $this->getResponse("post/$postId/downvote");

		$this->assertEquals(0, $downVotedPost['payload']['upvotes']);
		$this->assertEquals(1, $downVotedPost['payload']['downvotes']);
	}

	public function testPostWasDownVotedThenUpVoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedPost = $this->getResponse("post/$postId/downvote");

		$this->assertEquals(0, $upVotedPost['payload']['upvotes']);
		$this->assertEquals(1, $upVotedPost['payload']['downvotes']);

		$downVotedPost = $this->getResponse("post/$postId/upvote");

		$this->assertEquals(1, $downVotedPost['payload']['upvotes']);
		$this->assertEquals(0, $downVotedPost['payload']['downvotes']);
	}

	public function testPostWasUpVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedPost = $this->getResponse("post/$postId/upvote");

		$this->assertEquals(1, $upVotedPost['payload']['upvotes']);
		$this->assertEquals(0, $upVotedPost['payload']['downvotes']);

		$downVotedPost = $this->getResponse("post/$postId/unvote");

		$this->assertEquals(0, $downVotedPost['payload']['upvotes']);
		$this->assertEquals(0, $downVotedPost['payload']['downvotes']);
	}

	public function testPostWasDownVotedThenUnvoted(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$upVotedPost = $this->getResponse("post/$postId/downvote");

		$this->assertEquals(0, $upVotedPost['payload']['upvotes']);
		$this->assertEquals(1, $upVotedPost['payload']['downvotes']);

		$downVotedPost = $this->getResponse("post/$postId/unvote");

		$this->assertEquals(0, $downVotedPost['payload']['upvotes']);
		$this->assertEquals(0, $downVotedPost['payload']['downvotes']);
	}

	public function testPostWasVotedByMultipleUsers(): void
	{
		$postId = $this->generatePost()->getId();
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
				$this->getResponse("post/$postId/upvote");
				$upvotes++;

				if (rand(0, 1) === 0) {
					$this->getResponse("post/$postId/unvote");
					$upvotes--;
				}
			} else {
				$this->getResponse("post/$postId/downvote");
				$downvotes++;

				if (rand(0, 1) === 0) {
					$this->getResponse("post/$postId/unvote");
					$downvotes--;
				}
			}
		}

		$retrievedPost = $this->getResponse("post/$postId");

		$this->assertEquals($upvotes, $retrievedPost['payload']['upvotes']);
		$this->assertEquals($downvotes, $retrievedPost['payload']['downvotes']);
	}

	public function testPostWasNotUpVotedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("post/$postId/upvote");
		$upVotedPost = $this->getResponse("post/$postId/upvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $upVotedPost['status']);
		$this->assertEquals('Cannot up vote Post: Post has already been up voted.', $upVotedPost['message']);
		$this->assertEmpty($upVotedPost['payload']);
	}

	public function testPostWasNotDownVotedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("post/$postId/downvote");
		$downVotedPost = $this->getResponse("post/$postId/downvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $downVotedPost['status']);
		$this->assertEquals('Cannot down vote Post: Post has already been down voted.', $downVotedPost['message']);
		$this->assertEmpty($downVotedPost['payload']);
	}

	public function testPostWasNotUnvotedBeforeVoting(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$unvotedPost = $this->getResponse("post/$postId/unvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unvotedPost['status']);
		$this->assertEquals('Cannot unvote Post: Post must first be up or down voted.', $unvotedPost['message']);
		$this->assertEmpty($unvotedPost['payload']);
	}

	public function testPostWasNotUnvotedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("post/$postId/upvote");
		$this->getResponse("post/$postId/unvote");
		$unvotedPost = $this->getResponse("post/$postId/unvote");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unvotedPost['status']);
		$this->assertEquals('Cannot unvote Post: Post must first be up or down voted.', $unvotedPost['message']);
		$this->assertEmpty($unvotedPost['payload']);
	}
}
