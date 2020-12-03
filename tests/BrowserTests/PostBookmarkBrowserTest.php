<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\BrowserTests\BrowserTest;

final class PostBookmarkBrowserTest extends BrowserTest
{
	public function testPostWasBookmarked(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$bookmarkButton = $this->findElement('#post-bookmark-button');
		$bookmarkButtonText = $bookmarkButton->getText();

		$this->assertStringContainsString("Bookmark Post", $bookmarkButtonText);
		$bookmarkButton->click();

		/**
		 * Clicking on the bookmark button should trigger a JS fetch request.
		 * Selenium by default waits a small amount of time for a page "refresh"
		 * when clicking on a link or submitting a form. Since we're doing neither
		 * in this scenario, we have to manually tell Selenium to wait until the
		 * element (in this case, the element that contains the bookmark button text) changes.
		 */

		$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);
		$this->assertStringContainsString("Unbookmark Post", $bookmarkButton->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-post-bookmarks-button');
		$this->waitUntilElementLoads('table#user-post-bookmarks');

		$postBookmarksTableRows = $this->findElements('table#user-post-bookmarks tbody tr');

		foreach ($postBookmarksTableRows as $row) {
			$this->assertStringContainsString($post->getTitle(), $row->getText());
			$this->assertStringContainsString($post->getCreatedAt(), $row->getText());
			$this->assertStringContainsString($post->getUser()->getUsername(), $row->getText());
		}

		$this->logOut();
	}

	public function testPostWasUnbookmarked(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$post->bookmark($userId);
		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$bookmarkButton = $this->findElement('#post-bookmark-button');
		$bookmarkButtonText = $bookmarkButton->getText();
		$this->assertStringContainsString("Unbookmark Post", $bookmarkButtonText);
		$bookmarkButton->click();

		$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);

		$bookmarkButtonText = $bookmarkButton->getText();

		$this->assertStringContainsString("Bookmark Post", $bookmarkButtonText);
		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-post-bookmarks-button');
		$this->waitUntilElementLoads('table#user-post-bookmarks');
		$this->assertEmpty($this->findElements('table#user-post-bookmarks tbody tr'));

		$this->logOut();
	}

	public function testBookmarkInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$this->assertFalse($this->doesElementExist("#post-bookmark-button"));
	}

	public function testPostWasBookmarkedByMultipleUsers(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$numberOfUsers = rand(10, 20);

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$users[] = $this->generateUser(...array_values($userData));

			$this->logIn($userData['email'], $userData['password']);
			$this->goTo('/');
			$this->clickOnLink('category/{id}', ['id' => $categoryId]);
			$this->clickOnLink('post/{id}', ['id' => $postId]);

			$bookmarkButton = $this->findElement('#post-bookmark-button');
			$bookmarkButtonText = $bookmarkButton->getText();
			$postIsBookmarked[$i] = false;

			if (rand(0, 1) === 0) {
				$bookmarkButton->click();
				$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);
				$postIsBookmarked[$i] = true;

				if (rand(0, 1) === 0) {
					$bookmarkButtonText = $bookmarkButton->getText();
					$bookmarkButton->click();
					$this->waitUntilElementChanges($bookmarkButton, $bookmarkButtonText);
					$postIsBookmarked[$i] = false;
				}
			}

			$this->logout();
		}

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$this->goTo('user/{id}', ['id' => $i + 1]);
			$this->clickOnButton('#show-user-post-bookmarks-button');
			$this->waitUntilElementLoads('table#user-post-bookmarks');

			$postBookmarksTableRows = $this->findElements('table#user-post-bookmarks tbody tr');

			if ($postIsBookmarked[$i]) {
				foreach ($postBookmarksTableRows as $row) {
					$this->assertStringContainsString($post->getTitle(), $row->getText());
					$this->assertStringContainsString($post->getCreatedAt(), $row->getText());
					$this->assertStringContainsString($post->getUser()->getUsername(), $row->getText());
				}
			} else {
				$this->assertEmpty($this->findElements('table#user-post-bookmarks tbody tr'));
			}
		}
	}
}
