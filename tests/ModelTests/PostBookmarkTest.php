<?php

namespace AssignmentSixTests\ModelTests;

use AssignmentSix\Exceptions\PostException;
use AssignmentSixTests\ModelTests\ModelTest;

final class PostBookmarkTest extends ModelTest
{
	public function testPostWasBookmarked(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertEmpty($user->getBookmarkedPosts());
		$this->assertTrue($post->bookmark($user->getId()));

		$bookmarkedPosts = $user->getBookmarkedPosts();

		$this->assertEquals(1, sizeOf($bookmarkedPosts));
		$this->assertEquals($post->getId(), $bookmarkedPosts[0]->getId());
	}

	public function testPostWasUnbookmarked(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost();

		$this->assertTrue($post->bookmark($user->getId()));

		$bookmarkedPosts = $user->getBookmarkedPosts();

		$this->assertEquals(1, sizeOf($bookmarkedPosts));
		$this->assertTrue($post->unbookmark($user->getId()));

		$bookmarkedPosts = $user->getBookmarkedPosts();

		$this->assertEmpty($user->getBookmarkedPosts());
	}

	public function testPostWasBookmarkedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$post = $this->generatePost();

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$users[] = $this->generateUser();
			$userId = $users[$i]->getId();
			$postIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$post->bookmark($userId);
				$postIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$post->unbookmark($userId);
					$postIsBookmarked[$i] = false;
				}
			}
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$bookmarkedPosts = $users[$i]->getBookmarkedPosts();

			if ($postIsBookmarked[$i]) {
				$this->assertEquals(1, sizeOf($bookmarkedPosts));
				$this->assertEquals($post->getId(), $bookmarkedPosts[0]->getId());
			} else {
				$this->assertEmpty($bookmarkedPosts);
			}
		}
	}

	public function testExceptionWasThrownWhenBookmarkingPostTwice(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot bookmark Post: Post has already been bookmarked.");

		$user = $this->generateUser();
		$post = $this->generatePost();

		$post->bookmark($user->getId());
		$post->bookmark($user->getId());
	}

	public function testExceptionWasThrownWhenUnbookmarkingPostTwice(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot unbookmark Post: Post has not been bookmarked.");

		$user = $this->generateUser();
		$post = $this->generatePost();

		$post->unbookmark($user->getId());
	}

	public function testExceptionWasThrownWhenUnbookmarkingBeforeBookmarking(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot unbookmark Post: Post has not been bookmarked.");

		$user = $this->generateUser();
		$post = $this->generatePost();

		$post->bookmark($user->getId());
		$post->unbookmark($user->getId());
		$post->unbookmark($user->getId());
	}
}
