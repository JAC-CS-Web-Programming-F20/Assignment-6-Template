<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\RouterTests\RouterTest;
use HttpStatusCode;

final class PostBookmarkRouterTest extends RouterTest
{
	public function testPostWasBookmarked(): void
	{
		$userData = $this->generateUserData();
		$userId = $this->generateUser(...array_values($userData))->getId();
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$bookmarkedPost = $this->getResponse("post/$postId/bookmark");

		$this->assertEquals(HttpStatusCode::OK, $bookmarkedPost['status']);
		$this->assertEquals('Post was bookmarked successfully!', $bookmarkedPost['message']);

		$bookmarkedPosts = $this->getResponse("user/$userId/postbookmarks");

		$this->assertEquals("User's post bookmarks were retrieved successfully!", $bookmarkedPosts['message']);
		$this->assertEquals(1, sizeOf($bookmarkedPosts['payload']));
		$this->assertEquals($postId, $bookmarkedPosts['payload'][0]['id']);
	}

	public function testPostWasUnbookmarked(): void
	{
		$userData = $this->generateUserData();
		$userId = $this->generateUser(...array_values($userData))->getId();
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("post/$postId/bookmark");
		$unbookmarkedPost = $this->getResponse("post/$postId/unbookmark");

		$this->assertEquals(HttpStatusCode::OK, $unbookmarkedPost['status']);
		$this->assertEquals('Post was unbookmarked successfully!', $unbookmarkedPost['message']);

		$bookmarkedPosts = $this->getResponse("user/$userId/postbookmarks");

		$this->assertEquals("User's post bookmarks were retrieved successfully!", $bookmarkedPosts['message']);
		$this->assertEmpty($bookmarkedPosts['payload']);
	}

	public function testPostWasNotBookmarkedWhenNotLoggedIn(): void
	{
		$postId = $this->generatePost()->getId();
		$bookmarkedPost = $this->getResponse("post/$postId/bookmark");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $bookmarkedPost['status']);
		$this->assertEquals('Cannot bookmark Post: You must be logged in.', $bookmarkedPost['message']);
		$this->assertEmpty($bookmarkedPost['payload']);
	}

	public function testPostWasNotUnbookmarkedWhenNotLoggedIn(): void
	{
		$postId = $this->generatePost()->getId();
		$unbookmarkedPost = $this->getResponse("post/$postId/unbookmark");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $unbookmarkedPost['status']);
		$this->assertEquals('Cannot unbookmark Post: You must be logged in.', $unbookmarkedPost['message']);
		$this->assertEmpty($unbookmarkedPost['payload']);
	}

	public function testPostWasBookmarkedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$postId = $this->generatePost()->getId();

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$users[] = $this->generateUser(...array_values($userData));
			$this->getResponse(
				'auth/login',
				'POST',
				$userData
			);

			$postIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$this->getResponse("post/$postId/bookmark");
				$postIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$this->getResponse("post/$postId/unbookmark");
					$postIsBookmarked[$i] = false;
				}
			}
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userId = $users[$i]->getId();
			$bookmarkedPosts = $this->getResponse("user/$userId/postbookmarks");

			if ($postIsBookmarked[$i]) {
				$this->assertEquals(1, sizeOf($bookmarkedPosts['payload']));
				$this->assertEquals($postId, $bookmarkedPosts['payload'][0]['id']);
			} else {
				$this->assertEmpty($bookmarkedPosts['payload']);
			}
		}
	}

	public function testPostWasNotBookmarkedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("post/$postId/bookmark");
		$bookmarkedPost = $this->getResponse("post/$postId/bookmark");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $bookmarkedPost['status']);
		$this->assertEquals('Cannot bookmark Post: Post has already been bookmarked.', $bookmarkedPost['message']);
		$this->assertEmpty($bookmarkedPost['payload']);
	}

	public function testPostWasNotUnbookmarkedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("post/$postId/bookmark");
		$this->getResponse("post/$postId/unbookmark");
		$unbookmarkedPost = $this->getResponse("post/$postId/unbookmark");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unbookmarkedPost['status']);
		$this->assertEquals('Cannot unbookmark Post: Post has not been bookmarked.', $unbookmarkedPost['message']);
		$this->assertEmpty($unbookmarkedPost['payload']);
	}

	public function testPostWasNotUnbookmarkedBeforeBookmarking(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$postId = $this->generatePost()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$unbookmarkedPost = $this->getResponse("post/$postId/unbookmark");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unbookmarkedPost['status']);
		$this->assertEquals('Cannot unbookmark Post: Post has not been bookmarked.', $unbookmarkedPost['message']);
		$this->assertEmpty($unbookmarkedPost['payload']);
	}
}
