<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\RouterTests\RouterTest;
use HttpStatusCode;

final class PostRouterTest extends RouterTest
{
	public function testPostWasCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$postData = $this->generatePostData(null, $user);

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$response = $this->getResponse(
			'post',
			'POST',
			$postData
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertArrayHasKey('id', $response['payload']);
		$this->assertArrayHasKey('user', $response['payload']);
		$this->assertArrayHasKey('category', $response['payload']);
		$this->assertArrayHasKey('title', $response['payload']);
		$this->assertArrayHasKey('type', $response['payload']);
		$this->assertArrayHasKey('content', $response['payload']);
		$this->assertEquals(1, $response['payload']['id']);
		$this->assertEquals($postData['userId'], $response['payload']['user']['id']);
		$this->assertEquals($postData['categoryId'], $response['payload']['category']['id']);
		$this->assertEquals($postData['title'], $response['payload']['title']);
		$this->assertEquals($postData['type'], $response['payload']['type']);
		$this->assertEquals($postData['content'], $response['payload']['content']);
	}

	/**
	 * @dataProvider createPostProvider
	 */
	public function testPostWasNotCreated(array $postData, string $message): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$response = $this->getResponse(
			'post',
			'POST',
			$postData
		);

		$this->assertEmpty($response['payload']);
		$this->assertEquals($message, $response['message']);
	}

	public function createPostProvider()
	{
		yield 'string user ID' => [
			[
				'userId' => 'abc',
				'categoryId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: User ID must be an integer.'
		];

		yield 'string category ID' => [
			[
				'userId' => 1,
				'categoryId' => 'abc',
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Category ID must be an integer.'
		];

		yield 'invalid category ID' => [
			[
				'userId' => 1,
				'categoryId' => 999,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Category does not exist with ID 999.'
		];

		yield 'blank title' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => '',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Missing title.'
		];

		yield 'blank type' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => '',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			"Cannot create Post: Type must be 'Text' or 'URL'."
		];

		yield 'blank content' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => ''
			],
			'Cannot create Post: Missing content.'
		];
	}

	public function testPostWasNotCreatedWhenNotLoggedIn(): void
	{
		$response = $this->getResponse(
			'post',
			'POST',
			$this->generatePostData()
		);

		$this->assertEquals('Cannot create Post: You must be logged in.', $response['message']);
		$this->assertEmpty($response['payload']);
	}

	public function testPostWasNotCreatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));
		$postData = $this->generatePostData(null, $userA);

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$createdPost = $this->getResponse(
			'post',
			'POST',
			$postData
		);

		$this->assertEquals('Cannot create Post: You cannot create a post for someone else!', $createdPost['message']);
		$this->assertEmpty($createdPost['payload']);
	}

	public function testPostWasFoundById(): void
	{
		$post = $this->generatePost();

		$retrievedPost = $this->getResponse('post/' . $post->getId())['payload'];

		$this->assertArrayHasKey('id', $retrievedPost);
		$this->assertArrayHasKey('user', $retrievedPost);
		$this->assertArrayHasKey('category', $retrievedPost);
		$this->assertArrayHasKey('title', $retrievedPost);
		$this->assertArrayHasKey('type', $retrievedPost);
		$this->assertArrayHasKey('content', $retrievedPost);
		$this->assertEquals($post->getId(), $retrievedPost['id']);
		$this->assertEquals($post->getUser()->getId(), $retrievedPost['user']['id']);
		$this->assertEquals($post->getCategory()->getId(), $retrievedPost['category']['id']);
		$this->assertEquals($post->getTitle(), $retrievedPost['title']);
		$this->assertEquals($post->getType(), $retrievedPost['type']);
		$this->assertEquals($post->getContent(), $retrievedPost['content']);
	}

	public function testPostWasNotFoundByWrongId(): void
	{
		$retrievedPost = $this->getResponse('post/1');

		$this->assertEquals('Cannot find Post: Post does not exist with ID 1.', $retrievedPost['message']);
		$this->assertEmpty($retrievedPost['payload']);
	}

	/**
	 * @dataProvider updatedPostProvider
	 */
	public function testPostWasUpdated(array $oldPostData, array $newPostData, array $editedFields): void
	{
		$this->generatePost();
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$oldPostData['userId'] = $user->getId();

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$oldPost = $this->getResponse(
			'post',
			'POST',
			$oldPostData
		)['payload'];

		$editedPost = $this->getResponse(
			'post/' . $oldPost['id'],
			'PUT',
			$newPostData
		)['payload'];

		/**
		 * Check every Post field against all the fields that were supposed to be edited.
		 * If the Post field is a field that's supposed to be edited, check if they're not equal.
		 * If the Post field is not supposed to be edited, check if they're equal.
		 */
		foreach ($oldPost as $oldPostKey => $oldPostValue) {
			foreach ($editedFields as $editedField) {
				if ($oldPostKey === $editedField) {
					$this->assertNotEquals($oldPostValue, $editedPost[$editedField]);
					$this->assertEquals($editedPost[$editedField], $newPostData[$editedField]);
				}
			}
		}
	}

	public function updatedPostProvider()
	{
		yield 'valid content' => [
			[
				'title' => 'Top 3 Pokemon',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey',
				'userId' => 1,
				'categoryId' => 1
			],
			['content' => 'Bulbasaur'],
			['content'],
		];
	}

	/**
	 * @dataProvider updatePostProvider
	 */
	public function testPostWasNotUpdated(int $postId, string $type, array $newPostData, string $message): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$this->generatePost($type, $user);

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$editedPost = $this->getResponse(
			'post/' . $postId,
			'PUT',
			$newPostData
		);

		$this->assertEquals($message, $editedPost['message']);
		$this->assertEmpty($editedPost['payload']);
	}

	public function updatePostProvider()
	{
		yield 'blank text content' => [
			1,
			'text',
			['content' => ''],
			'Cannot edit Post: Missing content.'
		];

		yield 'blank url content' => [
			1,
			'url',
			['content' => ''],
			'Cannot edit Post: Missing content.'
		];

		yield 'new url' => [
			1,
			'url',
			['content' => 'www.nintendo.com'],
			'Cannot edit Post: Only text posts are updateable.'
		];
	}

	public function testPostWasNotUpdatedWhenNotLoggedIn(): void
	{
		$post = $this->generatePost();

		$editedPost = $this->getResponse(
			'post/' . $post->getId(),
			'PUT',
			$this->generatePostData()
		);

		$this->assertEquals('Cannot edit Post: You must be logged in.', $editedPost['message']);
		$this->assertEmpty($editedPost['payload']);
	}

	public function testPostWasNotUpdatedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$post = $this->generatePost(null, $userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$editedPost = $this->getResponse(
			'post/' . $post->getId(),
			'PUT',
			$this->generatePostData()
		);

		$this->assertEquals('Cannot edit Post: You cannot edit a post that you did not create!', $editedPost['message']);
		$this->assertEmpty($editedPost['payload']);
	}

	public function testPostWasDeletedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$post = $this->generatePost(null, $user);

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$this->assertEmpty($post->getDeletedAt());

		$deletedPost = $this->getResponse(
			'post/' . $post->getId(),
			'DELETE'
		)['payload'];

		$this->assertEquals($post->getId(), $deletedPost['id']);
		$this->assertEquals($post->getTitle(), $deletedPost['title']);
		$this->assertEquals($post->getContent(), $deletedPost['content']);

		$retrievedPost = $this->getResponse('post/' . $post->getId(),)['payload'];

		$this->assertNotEmpty($retrievedPost['deletedAt']);
	}

	public function testPostWasNotDeletedWithInvalidId(): void
	{
		$userData = $this->generateUserData();
		$this->generateUser(...array_values($userData));

		$this->getResponse(
			'auth/login',
			'POST',
			$userData
		);

		$deletedPost = $this->getResponse(
			'post/999',
			'DELETE'
		);

		$this->assertEquals('Cannot delete Post: Post does not exist with ID 999.', $deletedPost['message']);
		$this->assertEmpty($deletedPost['payload']);
	}

	public function testPostWasNotDeletedWhenNotLoggedIn(): void
	{
		$user = $this->generateUser();
		$post = $this->generatePost(null, $user);

		$deletedPost = $this->getResponse(
			'post/' . $post->getId(),
			'DELETE',
			$this->generatePostData()
		);

		$this->assertEquals('Cannot delete Post: You must be logged in.', $deletedPost['message']);
		$this->assertEmpty($deletedPost['payload']);
	}

	public function testPostWasNotDeletedByAnotherUser(): void
	{
		$userA = $this->generateUser();
		$post = $this->generatePost(null, $userA);
		$userDataB = $this->generateUserData();
		$this->generateUser(...array_values($userDataB));

		$this->getResponse(
			'auth/login',
			'POST',
			$userDataB
		);

		$deletedPost = $this->getResponse(
			'post/' . $post->getId(),
			'DELETE'
		);

		$this->assertEquals('Cannot delete Post: You cannot delete a post that you did not create!', $deletedPost['message']);
		$this->assertEmpty($deletedPost['payload']);
	}
}
