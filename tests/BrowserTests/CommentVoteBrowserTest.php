<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\BrowserTests\BrowserTest;

final class CommentVoteBrowserTest extends BrowserTest
{
	public function testCommentWasUpVotedOnPostPage(): void
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

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");

		/**
		 * Clicking on the upvote button should trigger a JS fetch request.
		 * Selenium by default waits a small amount of time for a page "refresh"
		 * when clicking on a link or submitting a form. Since we're doing neither
		 * in this scenario, we have to manually tell Selenium to wait until the
		 * element (in this case, the element that contains the comment votes) changes.
		 */

		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");

		$this->assertEquals(1, $commentVotesElement->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-comment-votes-button');
		$this->waitUntilElementLoads('table#user-comment-votes');

		$commentBookmarksTableRows = $this->findElements('table#user-comment-votes tbody tr');

		foreach ($commentBookmarksTableRows as $row) {
			$this->assertStringContainsString($comment->getContent(), $row->getText());
			$this->assertStringContainsString($comment->getCreatedAt(), $row->getText());
			$this->assertStringContainsString($comment->getUser()->getUsername(), $row->getText());
		}

		$this->logOut();
	}

	public function testCommentWasUpVotedOnCommentPage(): void
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

		$commentVotesElement = $this->findElement('.comment-votes');
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton('.comment-upvote-button');
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());
		$this->goBack();

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");

		$this->assertEquals(1, $commentVotesElement->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-comment-votes-button');
		$this->waitUntilElementLoads('table#user-comment-votes');

		$commentBookmarksTableRows = $this->findElements('table#user-comment-votes tbody tr');

		foreach ($commentBookmarksTableRows as $row) {
			$this->assertStringContainsString($comment->getContent(), $row->getText());
			$this->assertStringContainsString($comment->getCreatedAt(), $row->getText());
			$this->assertStringContainsString($comment->getUser()->getUsername(), $row->getText());
		}

		$this->logOut();
	}

	public function testCommentWasDownVotedOnPostPage(): void
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

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$commentVotesElement = $this->findElement('.comment-votes');

		$this->assertEquals(-1, $commentVotesElement->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-comment-votes-button');
		$this->waitUntilElementLoads('table#user-comment-votes');

		$commentBookmarksTableRows = $this->findElements('table#user-comment-votes tbody tr');

		foreach ($commentBookmarksTableRows as $row) {
			$this->assertStringContainsString($comment->getContent(), $row->getText());
			$this->assertStringContainsString($comment->getCreatedAt(), $row->getText());
			$this->assertStringContainsString($comment->getUser()->getUsername(), $row->getText());
		}

		$this->logOut();
	}

	public function testCommentWasDownVotedOnCommentPage(): void
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

		$commentVotesElement = $this->findElement('.comment-votes');
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton('.comment-downvote-button');
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());
		$this->goBack();

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");

		$this->assertEquals(-1, $commentVotesElement->getText());

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-comment-votes-button');
		$this->waitUntilElementLoads('table#user-comment-votes');

		$commentBookmarksTableRows = $this->findElements('table#user-comment-votes tbody tr');

		foreach ($commentBookmarksTableRows as $row) {
			$this->assertStringContainsString($comment->getContent(), $row->getText());
			$this->assertStringContainsString($comment->getCreatedAt(), $row->getText());
			$this->assertStringContainsString($comment->getUser()->getUsername(), $row->getText());
		}

		$this->logOut();
	}

	public function testCommentVoteInterfaceNotVisibleOnPostPageWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$this->assertFalse($this->doesElementExist(".comment-upvote-button"));
		$this->assertFalse($this->doesElementExist(".comment-downvote-button"));
	}

	public function testCommentVoteInterfaceNotVisibleOnCommentPageWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$this->assertFalse($this->doesElementExist(".comment-upvote-button"));
		$this->assertFalse($this->doesElementExist(".comment-downvote-button"));
	}

	public function testCommentWasUpVotedThenDownVotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasUpVotedThenDownVotedOnCommentPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasDownVotedThenUpVotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(-1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasDownVotedThenUpVotedOnCommentPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(-1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasUpVotedThenUnvotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(0, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasUpVotedThenUnvotedOnCommentPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(0, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasDownVotedThenUnvotedOnPostPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(-1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(0, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasDownVotedThenUnvotedOnCommentPage(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(0, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(-1, $commentVotesElement->getText());

		$commentVotesElementText = $commentVotesElement->getText();

		$this->assertEquals(-1, $commentVotesElement->getText());
		$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
		$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
		$this->assertEquals(0, $commentVotesElement->getText());

		$this->logOut();
	}

	public function testCommentWasVotedByMultipleUsers(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();
		$postId = $comment->getPost()->getId();
		$categoryId = $comment->getPost()->getCategory()->getId();
		$numberOfUsers = rand(10, 20);
		$upvotes = 0;
		$downvotes = 0;

		for ($i = 0; $i < $numberOfUsers; $i++) {
			$userData = $this->generateUserData();
			$this->generateUser(...array_values($userData));

			$this->logIn($userData['email'], $userData['password']);
			$this->goTo('/');
			$this->clickOnLink('category/{id}', ['id' => $categoryId]);
			$this->clickOnLink('post/{id}', ['id' => $postId]);

			if (rand(0, 1) === 0) {
				$this->clickOnLink('comment/{id}', ['id' => $commentId]);
			}

			$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");
			$commentVotesElementText = $commentVotesElement->getText();

			if (rand(0, 1) === 0) {
				$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
				$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
				$upvotes++;

				if (rand(0, 1) === 0) {
					$commentVotesElementText = $commentVotesElement->getText();
					$this->clickOnButton(".comment-upvote-button[comment-id=\"$commentId\"]");
					$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
					$upvotes--;
				}
			} else {
				$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
				$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
				$downvotes++;

				if (rand(0, 1) === 0) {
					$commentVotesElementText = $commentVotesElement->getText();
					$this->clickOnButton(".comment-downvote-button[comment-id=\"$commentId\"]");
					$this->waitUntilElementChanges($commentVotesElement, $commentVotesElementText);
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
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");

		$this->assertEquals($totalVotes, $commentVotesElement->getText());
		$this->clickOnLink('comment/{id}', ['id' => $commentId]);

		$commentVotesElement = $this->findElement(".comment-votes[comment-id=\"$commentId\"]");

		$this->assertEquals($totalVotes, $commentVotesElement->getText());
	}
}
