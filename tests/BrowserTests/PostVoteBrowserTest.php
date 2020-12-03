<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\BrowserTests\BrowserTest;

final class PostVoteBrowserTest extends BrowserTest
{
	public function testPostWasUpVotedOnCategoryPage(): void
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

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");

		/**
		 * Clicking on the upvote button should trigger a JS fetch request.
		 * Selenium by default waits a small amount of time for a page "refresh"
		 * when clicking on a link or submitting a form. Since we're doing neither
		 * in this scenario, we have to manually tell Selenium to wait until the
		 * element (in this case, the element that contains the post votes) changes.
		 */

		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement('.post-votes');

		$this->assertEquals(1, $postVotesElement->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-post-votes-button');
		$this->waitUntilElementLoads('table#user-post-votes');

		$postBookmarksTableRows = $this->findElements('table#user-post-votes tbody tr');

		foreach ($postBookmarksTableRows as $row) {
			$this->assertStringContainsString($post->getTitle(), $row->getText());
		}

		$this->logOut();
	}

	public function testPostWasUpVotedOnPostPage(): void
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

		$postVotesElement = $this->findElement('.post-votes');
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton('.post-upvote-button');
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());
		$this->goBack();

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");

		$this->assertEquals(1, $postVotesElement->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-post-votes-button');
		$this->waitUntilElementLoads('table#user-post-votes');

		$postBookmarksTableRows = $this->findElements('table#user-post-votes tbody tr');

		foreach ($postBookmarksTableRows as $row) {
			$this->assertStringContainsString($post->getTitle(), $row->getText());
			$this->assertStringContainsString($post->getCreatedAt(), $row->getText());
			$this->assertStringContainsString($post->getUser()->getUsername(), $row->getText());
		}

		$this->logOut();
	}

	public function testPostWasDownVotedOnCategoryPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement('.post-votes');

		$this->assertEquals(-1, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasDownVotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement('.post-votes');
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton('.post-downvote-button');
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());
		$this->goBack();

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");

		$this->assertEquals(-1, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostVoteInterfaceNotVisibleOnCategoryPageWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();
		$categoryId = $post->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$this->assertFalse($this->doesElementExist(".post-upvote-button"));
		$this->assertFalse($this->doesElementExist(".post-downvote-button"));
	}

	public function testPostVoteInterfaceNotVisibleOnPostPageWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$this->assertFalse($this->doesElementExist(".post-upvote-button"));
		$this->assertFalse($this->doesElementExist(".post-downvote-button"));
	}

	public function testPostWasUpVotedThenDownVotedOnCategoryPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(1, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasUpVotedThenDownVotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(1, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasDownVotedThenUpVotedOnCategoryPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(-1, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasDownVotedThenUpVotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(-1, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasUpVotedThenUnvotedOnCategoryPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(1, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(0, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasUpVotedThenUnvotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(1, $postVotesElement->getText());
		$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(0, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasDownVotedThenUnvotedOnCategoryPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(-1, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(0, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasDownVotedThenUnvotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(0, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(-1, $postVotesElement->getText());

		$postVotesElementText = $postVotesElement->getText();

		$this->assertEquals(-1, $postVotesElement->getText());
		$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
		$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
		$this->assertEquals(0, $postVotesElement->getText());

		$this->logOut();
	}

	public function testPostWasVotedByMultipleUsers(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$numberOfUsers = rand(10, 20);
		$upvotes = 0;
		$downvotes = 0;

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$this->generateUser(...array_values($userData));

			$this->logIn($userData['email'], $userData['password']);
			$this->goTo('/');
			$this->clickOnLink('category/{id}', ['id' => $categoryId]);

			if (rand(0, 1) === 0) {
				$this->clickOnLink('post/{id}', ['id' => $postId]);
			}

			$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");
			$postVotesElementText = $postVotesElement->getText();

			if (rand(0, 1) === 0) {
				$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
				$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
				$upvotes++;

				if (rand(0, 1) === 0) {
					$postVotesElementText = $postVotesElement->getText();
					$this->clickOnButton(".post-upvote-button[post-id=\"$postId\"]");
					$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
					$upvotes--;
				}
			} else {
				$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
				$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
				$downvotes++;

				if (rand(0, 1) === 0) {
					$postVotesElementText = $postVotesElement->getText();
					$this->clickOnButton(".post-downvote-button[post-id=\"$postId\"]");
					$this->waitUntilElementChanges($postVotesElement, $postVotesElementText);
					$downvotes--;
				}
			}

			$this->logout();
		}

		$totalVotes = $upvotes - $downvotes;

		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");

		$this->assertEquals($totalVotes, $postVotesElement->getText());
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postVotesElement = $this->findElement(".post-votes[post-id=\"$postId\"]");

		$this->assertEquals($totalVotes, $postVotesElement->getText());
	}
}
