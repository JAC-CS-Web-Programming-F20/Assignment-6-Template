<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSix\Models\Post;
use AssignmentSixTests\BrowserTests\BrowserTest;

final class PostBrowserTest extends BrowserTest
{
	public function testPostWasCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);
		$categoryId = $category->getId();
		$postData = $this->generatePostData(null, $user, $category);

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$userIdInput = $this->findElement("form#new-post-form input[name=\"userId\"][type=\"hidden\"]");
		$categoryIdInput = $this->findElement("form#new-post-form input[name=\"categoryId\"][type=\"hidden\"]");
		$titleInput = $this->findElement("form#new-post-form input[name=\"title\"]");
		$typeSelect = $this->findSelectElement("form#new-post-form select");
		$contentInput = $this->findElement("form#new-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-post-form button");

		$this->assertEquals($user->getId(), $userIdInput->getAttribute('value'));
		$this->assertEquals($categoryId, $categoryIdInput->getAttribute("value"));

		$titleInput->sendKeys($postData['title']);
		$typeSelect->selectByValue($postData['type']);
		$contentInput->sendKeys($postData['content']);
		$submitButton->click();

		$post = Post::findByUser($postData['userId'])[0];
		$postId = $post->getId();
		$postElement = $this->findElement("tr[post-id=\"$postId\"]");

		$this->assertStringContainsString($postData['title'], $postElement->getText());
		$this->assertStringContainsString($post->getUser()->getUsername(), $postElement->getText());
		$this->assertStringContainsString($post->getCreatedAt(), $postElement->getText());
		$this->assertStringContainsString("No", $postElement->getText());

		$this->logOut();
	}

	public function testManyPostsWereCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$userId = $user->getId();
		$category = $this->generateCategory();

		$this->logIn($userData['email'], $userData['password']);

		for ($i = 0; $i < rand(2, 5); $i++) {
			$postData = $this->generatePostData(null, $user, $category);
			$categoryId = $postData['categoryId'];

			$this->goTo('/');
			$this->clickOnLink('category/{id}', ['id' => $categoryId]);

			$userIdInput = $this->findElement("form#new-post-form input[name=\"userId\"][type=\"hidden\"]");
			$categoryIdInput = $this->findElement("form#new-post-form input[name=\"categoryId\"][type=\"hidden\"]");
			$titleInput = $this->findElement("form#new-post-form input[name=\"title\"]");
			$typeSelect = $this->findSelectElement("form#new-post-form select");
			$contentInput = $this->findElement("form#new-post-form textarea[name=\"content\"]");
			$submitButton = $this->findElement("form#new-post-form button");

			$this->assertEquals($user->getId(), $userIdInput->getAttribute('value'));
			$this->assertEquals($categoryId, $categoryIdInput->getAttribute("value"));

			$titleInput->sendKeys($postData['title']);

			$typeSelect->selectByValue($postData['type']);

			$contentInput->sendKeys($postData['content']);

			$submitButton->click();

			$posts[] = Post::findById($i + 1);
			$postId = $posts[$i]->getId();
			$postElement = $this->findElement("tr[post-id=\"$postId\"]");

			$this->assertStringContainsString($postData['title'], $postElement->getText());
			$this->assertStringContainsString($posts[$i]->getUser()->getUsername(), $postElement->getText());
			$this->assertStringContainsString($posts[$i]->getCreatedAt(), $postElement->getText());
			$this->assertStringContainsString("No", $postElement->getText());
		}

		$this->clickOnLink('user/{id}', ['id' => $userId]);
		$this->clickOnButton('#show-user-posts-button');
		$this->waitUntilElementLoads('table#user-posts');

		$postBookmarksTableRows = $this->findElements('table#user-posts tbody tr');

		for ($i = 0; $i < sizeOf($posts); $i++) {
			$this->assertStringContainsString($posts[$i]->getTitle(), $postBookmarksTableRows[$i]->getText());
			$this->assertStringContainsString($posts[$i]->getCreatedAt(), $postBookmarksTableRows[$i]->getText());
		}

		$this->logOut();
	}

	/**
	 * @dataProvider createPostProvider
	 */
	public function testPostWasNotCreated(array $postData, string $message): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);
		$categoryId = $category->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$titleInput = $this->findElement("form#new-post-form input[name=\"title\"]");
		$typeSelect = $this->findSelectElement("form#new-post-form select");
		$contentInput = $this->findElement("form#new-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-post-form button");

		$titleInput->sendKeys($postData['title']);
		$typeSelect->selectByValue($postData['type']);
		$contentInput->sendKeys($postData['content']);
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString($message, $body->getText());

		$this->logOut();
	}

	public function createPostProvider()
	{
		yield 'blank title' => [
			[
				'title' => '',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Missing title.'
		];

		yield 'blank type' => [
			[
				'title' => 'Top 3 Pokemon!',
				'type' => '',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			"Cannot create Post: Type must be 'Text' or 'URL'."
		];

		yield 'blank content' => [
			[
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => ''
			],
			'Cannot create Post: Missing content.'
		];
	}

	public function testCreatePostInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$this->assertFalse($this->doesElementExist("form#new-post-form"));
	}

	public function testPostWasFoundById(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postTitle = $this->findElement("#post-title");
		$postContent = $this->findElement("#post-content");

		$this->assertStringContainsString($post->getTitle(), $postTitle->getText());
		$this->assertStringContainsString($post->getContent(), $postContent->getText());
	}

	public function testPostWasNotFoundByWrongId(): void
	{
		$randomPostId = rand(1, 100);
		$this->goTo('post/{id}', ['id' => $randomPostId]);

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot find Post: Post does not exist with ID $randomPostId.", $body->getText());
	}

	public function testTextPostWasUpdatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost('Text', $user);
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$newPostData = $this->generatePostData('Text', $user);

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('post/{id}/edit', ['id' => $postId]);

		$contentInput = $this->findElement("form#edit-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-post-form button");

		$this->assertStringContainsString($post->getContent(), $contentInput->getText());

		$contentInput->sendKeys($newPostData['content']);
		$submitButton->click();

		$postTitle = $this->findElement('#post-title');
		$postContent = $this->findElement('#post-content');

		$this->assertStringContainsString($post->getTitle(), $postTitle->getText());
		$this->assertStringContainsString($newPostData['content'], $postContent->getText());

		$this->logOut();
	}

	public function testTextPostWasNotUpdatedWithBlankContent(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost('Text', $user);
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnLink('post/{id}/edit', ['id' => $postId]);

		$contentInput = $this->findElement("form#edit-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-post-form button");

		$this->assertStringContainsString($post->getContent(), $contentInput->getAttribute('value'));

		$contentInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString('Cannot edit Post: Missing content.', $body->getText());

		$this->logOut();
	}

	public function testNoUpdateInterfaceForUrlPost(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost('URL', $user);
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$this->assertFalse($this->doesElementExist("form#edit-post-form"));

		$this->logOut();
	}

	public function testUpdatePostInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postTitle = $this->findElement("#post-title");
		$postContent = $this->findElement("#post-content");

		$this->assertStringContainsString($post->getTitle(), $postTitle->getText());
		$this->assertStringContainsString($post->getContent(), $postContent->getText());
		$this->assertFalse($this->doesElementExist("a[href*=\"" . \Url::path('post/{id}/edit', ['id' => $postId]) . "\"]"));
	}

	public function testPostWasDeletedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost('URL', $user);
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);
		$this->clickOnButton('form#delete-post-form button');

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$postsRow = $this->findElement("tr[post-id=\"$postId\"]");

		$this->assertStringContainsString($post->getTitle(), $postsRow->getText());
		$this->assertStringContainsString($post->getUser()->getUsername(), $postsRow->getText());
		$this->assertStringContainsString('Yes', $postsRow->getText());

		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$this->assertFalse($this->doesElementExist('form#edit-post-form'));
		$this->assertFalse($this->doesElementExist('form#delete-post-form'));
		$this->assertFalse($this->doesElementExist('form#new-comment-form'));

		$this->logOut();
	}

	public function testDeletePostInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('post/{id}', ['id' => $postId]);

		$postTitle = $this->findElement("#post-title");
		$postContent = $this->findElement("#post-content");

		$this->assertStringContainsString($post->getTitle(), $postTitle->getText());
		$this->assertStringContainsString($post->getContent(), $postContent->getText());
		$this->assertFalse($this->doesElementExist("form#delete-post-form"));
	}
}
