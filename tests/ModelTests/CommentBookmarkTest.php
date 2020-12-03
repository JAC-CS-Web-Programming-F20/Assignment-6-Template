<?php

namespace AssignmentSixTests\ModelTests;

use AssignmentSix\Exceptions\CommentException;
use AssignmentSixTests\ModelTests\ModelTest;

final class CommentBookmarkTest extends ModelTest
{
	public function testCommentWasBookmarked(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertEmpty($user->getBookmarkedComments());
		$this->assertTrue($comment->bookmark($user->getId()));

		$bookmarkedComments = $user->getBookmarkedComments();

		$this->assertEquals(1, sizeOf($bookmarkedComments));
		$this->assertEquals($comment->getId(), $bookmarkedComments[0]->getId());
	}

	public function testCommentWasUnbookmarked(): void
	{
		$user = $this->generateUser();
		$comment = $this->generateComment();

		$this->assertTrue($comment->bookmark($user->getId()));

		$bookmarkedComments = $user->getBookmarkedComments();

		$this->assertEquals(1, sizeOf($bookmarkedComments));
		$this->assertTrue($comment->unbookmark($user->getId()));

		$bookmarkedComments = $user->getBookmarkedComments();

		$this->assertEmpty($user->getBookmarkedComments());
	}

	public function testCommentWasBookmarkedByMultipleUsers(): void
	{
		$numberOfUsers = rand(10, 20);
		$comment = $this->generateComment();

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$users[] = $this->generateUser();
			$userId = $users[$i]->getId();
			$commentIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$comment->bookmark($userId);
				$commentIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$comment->unbookmark($userId);
					$commentIsBookmarked[$i] = false;
				}
			}
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$bookmarkedComments = $users[$i]->getBookmarkedComments();

			if ($commentIsBookmarked[$i]) {
				$this->assertEquals(1, sizeOf($bookmarkedComments));
				$this->assertEquals($comment->getId(), $bookmarkedComments[0]->getId());
			} else {
				$this->assertEmpty($bookmarkedComments);
			}
		}
	}

	public function testExceptionWasThrownWhenBookmarkingCommentTwice(): void
	{
		$this->expectException(CommentException::class);
		$this->expectExceptionMessage("Cannot bookmark Comment: Comment has already been bookmarked.");

		$user = $this->generateUser();
		$comment = $this->generateComment();

		$comment->bookmark($user->getId());
		$comment->bookmark($user->getId());
	}

	public function testExceptionWasThrownWhenUnbookmarkingCommentTwice(): void
	{
		$this->expectException(CommentException::class);
		$this->expectExceptionMessage("Cannot unbookmark Comment: Comment has not been bookmarked.");

		$user = $this->generateUser();
		$comment = $this->generateComment();

		$comment->unbookmark($user->getId());
	}

	public function testExceptionWasThrownWhenUnbookmarkingBeforeBookmarking(): void
	{
		$this->expectException(CommentException::class);
		$this->expectExceptionMessage("Cannot unbookmark Comment: Comment has not been bookmarked.");

		$user = $this->generateUser();
		$comment = $this->generateComment();

		$comment->bookmark($user->getId());
		$comment->unbookmark($user->getId());
		$comment->unbookmark($user->getId());
	}
}
