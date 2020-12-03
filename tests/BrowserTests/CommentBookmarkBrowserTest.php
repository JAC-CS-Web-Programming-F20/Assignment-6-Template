<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\BrowserTests\BrowserTest;

final class CommentBookmarkBrowserTest extends BrowserTest
{
	public function testCommentWasBookmarked(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$bookmarkButton = $this->findElement(".comment[comment-id=\"$commentId\"] .comment-bookmark-button");
		$bookmarkButtonText = $bookmarkButton->getText();

		$this->assertStringContainsString("Bookmark Comment", $bookmarkButtonText);
		$bookmarkButton->click();

		/**
		 * Clicking on the bookmark button should trigger a JS fetch request.
		 * Selenium by default waits a small amount of time for a page "refresh"
		 * when clicking on a link or submitting a form. Since we're doing neither
		 * in this scenario, we have to manually tell Selenium to wait until the
		 * element (in this case, the element that contains the bookmark button text) changes.
		 */

		$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);
		$this->assertStringContainsString("Unbookmark Comment", $bookmarkButton->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-comment-bookmarks-button');
		$this->waitUntilElementLoads('table#user-comment-bookmarks');

		$commentBookmarksTableRows = $this->findElements('table#user-comment-bookmarks tbody tr');

		foreach ($commentBookmarksTableRows as $row) {
			$this->assertStringContainsString($comment->getContent(), $row->getText());
			$this->assertStringContainsString($comment->getCreatedAt(), $row->getText());
			$this->assertStringContainsString($comment->getUser()->getUsername(), $row->getText());
		}

		$this->logOut();
	}

	public function testCommentWasUnbookmarked(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$comment->bookmark($userId);
		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$bookmarkButton = $this->findElement(".comment[comment-id=\"$commentId\"] .comment-bookmark-button");
		$bookmarkButtonText = $bookmarkButton->getText();
		$this->assertStringContainsString("Unbookmark Comment", $bookmarkButtonText);
		$bookmarkButton->click();

		$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);

		$bookmarkButtonText = $bookmarkButton->getText();

		$this->assertStringContainsString("Bookmark Comment", $bookmarkButtonText);
		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-comment-bookmarks-button');
		$this->waitUntilElementLoads('table#user-comment-bookmarks');
		$this->assertEmpty($this->findElements('table#user-comment-bookmarks tbody tr'));

		$this->logOut();
	}

	public function testBookmarkInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$this->assertFalse($this->doesElementExist(".comment-bookmark-button"));
	}

	public function testCommentWasBookmarkedByMultipleUsers(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();
		$numberOfUsers = rand(10, 20);

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$users[] = $this->generateUser(...array_values($userData));

			$this->logIn($userData['email'], $userData['password']);
			$this->goTo('/');
			$this->clickOnLink('category/{id}', ['id' => $categoryId]);
			$this->clickOnLink('post/{id}', ['id' => $postId]);
			$this->clickOnLink('comment/{id}', ['id' => $commentId]);

			$bookmarkButton = $this->findElement(".comment[comment-id=\"$commentId\"] .comment-bookmark-button");
			$bookmarkButtonText = $bookmarkButton->getText();
			$commentIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$bookmarkButton->click();
				$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);
				$commentIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$bookmarkButtonText = $bookmarkButton->getText();
					$bookmarkButton->click();
					$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);
					$commentIsBookmarked[$i] = false;
				}
			}

			$this->logout();
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$this->goTo('user/{id}', ['id' => $i + 1]);
			$this->clickOnButton('#show-user-comment-bookmarks-button');
			$this->waitUntilElementLoads('table#user-comment-bookmarks');

			$commentBookmarksTableRows = $this->findElements('table#user-comment-bookmarks tbody tr');

			if ($commentIsBookmarked[$i]) {
				foreach ($commentBookmarksTableRows as $row) {
					$this->assertStringContainsString($comment->getContent(), $row->getText());
					$this->assertStringContainsString($comment->getCreatedAt(), $row->getText());
					$this->assertStringContainsString($comment->getUser()->getUsername(), $row->getText());
				}
			} else {
				$this->assertEmpty($this->findElements('table#user-comment-bookmarks tbody tr'));
			}
		}
	}
}
