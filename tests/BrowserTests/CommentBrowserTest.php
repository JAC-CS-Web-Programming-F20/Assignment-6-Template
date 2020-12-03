<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSix\Models\Comment;
use AssignmentSixTests\BrowserTests\BrowserTest;

final class CommentBrowserTest extends BrowserTest
{
	public function testCommentWasCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$post = $this->generatePost(null, null, $category);
		$postId = $post->getId();
		$commentData = $this->generateCommentData($user, $post);

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$userIdInput = $this->findElement("form#new-comment-form input[name=\"userId\"][type=\"hidden\"]");
		$postIdInput = $this->findElement("form#new-comment-form input[name=\"postId\"][type=\"hidden\"]");
		$contentInput = $this->findElement("form#new-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-comment-form button");

		$this->assertStringContainsString($user->getId(), $userIdInput->getAttribute('value'));
		$this->assertStringContainsString($postId, $postIdInput->getAttribute('value'));

		$contentInput->sendKeys($commentData['content']);
		$submitButton->click();

		$comment = Comment::findByPost($postId)[0];
		$commentId = $comment->getId();
		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($commentData['content'], $commentElement->getText());
		$this->assertStringContainsString($comment->getUser()->getUsername(), $commentElement->getText());
		$this->assertStringContainsString($comment->getCreatedAt(), $commentElement->getText());

		$this->logOut();
	}

	public function testManyCommentsWereCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);

		for ($i = 0; $i < rand(2, 5); $i++) {
			$commentData = $this->generateCommentData($user, $post);

			$this->goTo('/');
			$this->clickOnLink('category/{id}', ['id' => $categoryId]);
			$this->clickOnLink('post/{id}', ['id' => $postId]);

			$contentInput = $this->findElement("form#new-comment-form textarea[name=\"content\"]");
			$submitButton = $this->findElement("form#new-comment-form button");

			$contentInput->sendKeys($commentData['content']);

			$submitButton->click();

			$comments[] = Comment::findByPost($postId)[$i];
			$commentId = $comments[$i]->getId();
			$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

			$this->assertStringContainsString($commentData['content'], $commentElement->getText());
			$this->assertStringContainsString($comments[$i]->getUser()->getUsername(), $commentElement->getText());
			$this->assertStringContainsString($comments[$i]->getCreatedAt(), $commentElement->getText());
		}

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-comments-button');
		$this->waitUntilElementLoads('table#user-comments');

		$postBookmarksTableRows = $this->findElements('table#user-comments tbody tr');

		for ($i = 0; $i < sizeOf($comments); $i++) {
			$this->assertStringContainsString($comments[$i]->getContent(), $postBookmarksTableRows[$i]->getText());
			$this->assertStringContainsString($comments[$i]->getPost()->getTitle(), $postBookmarksTableRows[$i]->getText());
			$this->assertStringContainsString($comments[$i]->getCreatedAt(), $postBookmarksTableRows[$i]->getText());
		}

		$this->logOut();
	}

	public function testCommentWasNotCreatedWithBlankContent(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$post = $this->generatePost(null, null, $category);
		$postId = $post->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$contentInput = $this->findElement("form#new-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-comment-form button");

		$contentInput->sendKeys('');
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot create Comment: Missing content.", $body->getText());

		$this->logOut();
	}

	public function testCreateCommentInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$post = $this->generatePost(null, null, $category);
		$postId = $post->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$this->assertFalse($this->doesElementExist("form#new-comment-form"));
	}

	public function testCommentWasFoundById(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		$this->goTo('comment/{id}', ['id' => $commentId]);

		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($comment->getUser()->getUsername(), $commentElement->getText());
		$this->assertStringContainsString($comment->getContent(), $commentElement->getText());
		$this->assertStringContainsString($comment->getCreatedAt(), $commentElement->getText());
	}

	/**
	 * @dataProvider commentAndRepliesProvider
	 */
	public function testCommentAndRepliesWereFound(array $commentOrder, array $answers): void
	{
		$post = $this->generatePost();
		$comments = [];

		foreach ($commentOrder as $comment) {
			if ($comment === null) {
				$comments[] = $this->generateComment(null, $post, null);
			} else {
				$comments[] = $this->generateComment(null, $post, $comments[$comment]);
			}
		}

		for ($i = 0; $i < sizeOf($comments); $i++) {
			$commentId = $comments[$i]->getId();

			$this->goTo('comment/{id}', ['id' => $commentId]);

			$commentElements = $this->findElements(".comment");

			$this->assertEquals($answers[$i], sizeOf($commentElements));

			$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

			$this->assertStringContainsString($comments[$i]->getUser()->getUsername(), $commentElement->getText());
			$this->assertStringContainsString($comments[$i]->getContent(), $commentElement->getText());
			$this->assertStringContainsString($comments[$i]->getCreatedAt(), $commentElement->getText());
		}
	}

	/**
	 * The first array represents the comments that will be created in the test.
	 * [null] means one comment will be created with null as the parent.
	 * [null, 0] means one comment will be created with null as the parent and
	 * the next comment will be created with its parent as the comment at index 0. Index
	 * 0 being the first comment with null as the parent, in this case.
	 *
	 * The second array contains the number of comments that should be displayed on
	 * the page when the corresponding comment in the first array is requested.
	 * In the first scenario, only one comment is created in the first array so the
	 * second array says there should only be one comment displayed for when that comment
	 * is requested. In the second scenario, if we request the comment whose parent is null,
	 * we should get back 2 comments: the requested comment and its one reply. If we request
	 * the second comment, we should only get back one comment: the requested comment and no
	 * replies.
	 */
	public function commentAndRepliesProvider()
	{
		yield 'scenario 1' => [
			[null],
			[1]
		];

		yield 'scenario 2' => [
			[null, 0],
			[2, 1]
		];

		yield 'scenario 3' => [
			[null, 0, 1, 0],
			[4, 2, 1, 1]
		];

		yield 'scenario 4' => [
			[null, 0, 0, null, 3, 3],
			[3, 1, 1, 3, 1, 1]
		];

		yield 'scenario 5' => [
			[null, 0, 0, 0, 1, 1, 2, 6, 6, 8],
			[10, 3, 5, 1, 1, 1, 4, 1, 2, 1]
		];
	}

	public function testCommentWasNotFoundByWrongId(): void
	{
		$randomCommentId = rand(1, 100);

		$this->goTo('comment/{id}', ['id' => $randomCommentId]);

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot find Comment: Comment does not exist with ID $randomCommentId.", $body->getText());
	}

	public function testCommentWasUpdatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$post = $this->generatePost(null, null, $category);
		$postId = $post->getId();
		$comment = $this->generateComment($user, $post);
		$commentId = $comment->getId();
		$newCommentData = $this->generateCommentData();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}/edit', ['id' => $commentId]);

		$contentInput = $this->findElement("form#edit-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-comment-form button");

		$this->assertStringContainsString($comment->getContent(), $contentInput->getText());

		$contentInput->sendKeys($newCommentData['content']);
		$submitButton->click();

		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($newCommentData['content'], $commentElement->getText());

		$this->logOut();
	}

	public function testReplyWasUpdatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$post = $this->generatePost(null, null, $category);
		$postId = $post->getId();
		$comment = $this->generateComment(null, $post);
		$reply = $this->generateComment($user, $post, $comment);
		$replyId = $reply->getId();
		$newCommentData = $this->generateCommentData();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}/edit', ['id' => $replyId]);

		$contentInput = $this->findElement("form#edit-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-comment-form button");

		$this->assertStringContainsString($reply->getContent(), $contentInput->getText());

		$contentInput->sendKeys($newCommentData['content']);
		$submitButton->click();

		$commentElement = $this->findElement(".comment[comment-id=\"$replyId\"]");

		$this->assertStringContainsString($newCommentData['content'], $commentElement->getText());

		$this->logOut();
	}

	public function testCommentWasNotUpdatedWithBlankContent(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$post = $this->generatePost(null, null, $category);
		$postId = $post->getId();
		$comment = $this->generateComment($user, $post);
		$commentId = $comment->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('comment/{id}/edit', ['id' => $commentId]);

		$contentInput = $this->findElement("form#edit-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-comment-form button");

		$this->assertStringContainsString($comment->getContent(), $contentInput->getText());

		$contentInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot edit Comment: Missing content.", $body->getText());

		$this->logOut();
	}

	public function testUpdateCommentInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$categoryId = $comment->getPost()->getCategory()->getId();
		$postId = $comment->getPost()->getId();
		$commentId = $comment->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($comment->getContent(), $commentElement->getText());
		$this->assertFalse($this->doesElementExist("a[href*=\"" . \Url::path('comment/{id}/edit', ['id' => $commentId]) . "\"]"));
	}

	public function testCommentWasDeletedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$post = $this->generatePost(null, null, $category);
		$postId = $post->getId();
		$comment = $this->generateComment($user, $post);
		$commentId = $comment->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnButton(".comment[comment-id=\"$commentId\"] form.delete-comment-form button");

		$this->assertEquals(\Url::path('post/{id}', ['id' => $postId]), $this->getCurrentUrl());

		$deletedComment  = $this->findElement(".comment[comment-id=\"$commentId\"]");
		$deletedAt = Comment::findById($commentId)->getDeletedAt();

		$this->assertStringContainsString("Comment was deleted on $deletedAt", $deletedComment->getText());
		$this->assertFalse($this->doesElementExist(".comment[comment-id=\"$commentId\"] a[href*=\"/edit\"]"));
		$this->assertFalse($this->doesElementExist(".comment[comment-id=\"$commentId\"] form.delete-comment-form"));
		$this->assertFalse($this->doesElementExist(".comment[comment-id=\"$commentId\"] form.new-reply-form"));

		$this->logOut();
	}

	public function testDeleteCommentInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$comment = $this->generateComment();
		$categoryId = $comment->getPost()->getCategory()->getId();
		$postId = $comment->getPost()->getId();
		$commentId = $comment->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($comment->getContent(), $commentElement->getText());
		$this->assertFalse($this->doesElementExist("form#delete-comment-form"));
	}
}
