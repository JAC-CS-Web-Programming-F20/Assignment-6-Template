<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\RouterTests\RouterTest;
use HttpStatusCode;

final class CommentBookmarkRouterTest extends RouterTest
{
	public function testCommentWasBookmarked(): void
	{
		$userData = $this->generateUserData();
		$userId = $this->generateUser(...array_values($userData))->getId();
		$commentId = $this->generateComment()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$bookmarkedComment = $this->getResponse("comment/$commentId/bookmark");

		$this->assertEquals(HttpStatusCode::OK, $bookmarkedComment['status']);
		$this->assertEquals('Comment was bookmarked successfully!', $bookmarkedComment['message']);

		$bookmarkedComments = $this->getResponse("user/$userId/commentbookmarks");

		$this->assertEquals("User's comment bookmarks were retrieved successfully!", $bookmarkedComments['message']);
		$this->assertEquals(1, sizeOf($bookmarkedComments['payload']));
		$this->assertEquals($commentId, $bookmarkedComments['payload'][0]['id']);
	}

	public function testCommentWasUnbookmarked(): void
	{
		$userData = $this->generateUserData();
		$userId = $this->generateUser(...array_values($userData))->getId();
		$commentId = $this->generateComment()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("comment/$commentId/bookmark");
		$unbookmarkedComment = $this->getResponse("comment/$commentId/unbookmark");

		$this->assertEquals(HttpStatusCode::OK, $unbookmarkedComment['status']);
		$this->assertEquals('Comment was unbookmarked successfully!', $unbookmarkedComment['message']);

		$bookmarkedComments = $this->getResponse("user/$userId/commentbookmarks");

		$this->assertEquals("User's comment bookmarks were retrieved successfully!", $bookmarkedComments['message']);
		$this->assertEmpty($bookmarkedComments['payload']);
	}

	public function testCommentWasNotBookmarkedWhenNotLoggedIn(): void
	{
		$commentId = $this->generateComment()->getId();
		$bookmarkedComment = $this->getResponse("comment/$commentId/bookmark");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $bookmarkedComment['status']);
		$this->assertEquals('Cannot bookmark Comment: You must be logged in.', $bookmarkedComment['message']);
		$this->assertEmpty($bookmarkedComment['payload']);
	}

	public function testCommentWasNotUnbookmarkedWhenNotLoggedIn(): void
	{
		$commentId = $this->generateComment()->getId();
		$unbookmarkedComment = $this->getResponse("comment/$commentId/unbookmark");

		$this->assertEquals(HttpStatusCode::UNAUTHORIZED, $unbookmarkedComment['status']);
		$this->assertEquals('Cannot unbookmark Comment: You must be logged in.', $unbookmarkedComment['message']);
		$this->assertEmpty($unbookmarkedComment['payload']);
	}

	public function testCommentWasBookmarkedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$commentId = $this->generateComment()->getId();

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$users[] = $this->generateUser(...array_values($userData));
			$this->getResponse(
				'auth/login',
				'POST',
				$userData
			);

			$commentIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$this->getResponse("comment/$commentId/bookmark");
				$commentIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$this->getResponse("comment/$commentId/unbookmark");
					$commentIsBookmarked[$i] = false;
				}
			}
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userId = $users[$i]->getId();
			$bookmarkedComments = $this->getResponse("user/$userId/commentbookmarks");

			if ($commentIsBookmarked[$i]) {
				$this->assertEquals(1, sizeOf($bookmarkedComments['payload']));
				$this->assertEquals($commentId, $bookmarkedComments['payload'][0]['id']);
			} else {
				$this->assertEmpty($bookmarkedComments['payload']);
			}
		}
	}

	public function testCommentWasNotBookmarkedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$commentId = $this->generateComment()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("comment/$commentId/bookmark");
		$bookmarkedComment = $this->getResponse("comment/$commentId/bookmark");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $bookmarkedComment['status']);
		$this->assertEquals('Cannot bookmark Comment: Comment has already been bookmarked.', $bookmarkedComment['message']);
		$this->assertEmpty($bookmarkedComment['payload']);
	}

	public function testCommentWasNotUnbookmarkedTwice(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$commentId = $this->generateComment()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->getResponse("comment/$commentId/bookmark");
		$this->getResponse("comment/$commentId/unbookmark");
		$unbookmarkedComment = $this->getResponse("comment/$commentId/unbookmark");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unbookmarkedComment['status']);
		$this->assertEquals('Cannot unbookmark Comment: Comment has not been bookmarked.', $unbookmarkedComment['message']);
		$this->assertEmpty($unbookmarkedComment['payload']);
	}

	public function testCommentWasNotUnbookmarkedBeforeBookmarking(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$commentId = $this->generateComment()->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$unbookmarkedComment = $this->getResponse("comment/$commentId/unbookmark");

		$this->assertEquals(HttpStatusCode::BAD_REQUEST, $unbookmarkedComment['status']);
		$this->assertEquals('Cannot unbookmark Comment: Comment has not been bookmarked.', $unbookmarkedComment['message']);
		$this->assertEmpty($unbookmarkedComment['payload']);
	}
}
