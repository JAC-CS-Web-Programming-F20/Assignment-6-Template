<?php

namespace AssignmentSixTests;

use AssignmentSix\Database\Connection;
use AssignmentSix\Models\{Category, Comment, Post, User};
use Faker\Generator;
use PHPUnit\Framework\TestCase;

abstract class AssignmentSixTest extends TestCase
{
	protected static Generator $faker;

	protected static function generateUserData(string $username = null, string $email = null, string $password = null): array
	{
		return [
			'username' => $username ?? self::$faker->username,
			'email' => $email ?? self::$faker->email,
			'password' => $password ?? self::$faker->password
		];
	}

	protected static function generateUser(string $username = null, string $email = null, string $password = null): User
	{
		$userData = self::generateUserData($username, $email, $password);

		return User::create(
			$userData['username'],
			$userData['email'],
			$userData['password']
		);
	}

	protected static function generateCategory(User $user = null, string $title = null): Category
	{
		$user = $user ?? self::generateUser();
		$categoryData = self::generateCategoryData($user, $title);

		return Category::create(
			$categoryData['createdBy'],
			$categoryData['title'],
			$categoryData['description']
		);
	}

	protected static function generateCategoryData(User $user = null, string $title = null): array
	{
		$user = $user ?? self::generateUser();
		$title = $title ?? self::$faker->word;

		while (Category::findByTitle($title)) {
			$title = self::$faker->word;
		}

		return [
			'createdBy' => $user->getId(),
			'title' => $title,
			'description' => self::$faker->sentence
		];
	}

	protected static function generatePost(string $type = null, User $user = null, Category $category = null): Post
	{
		$postData = self::generatePostData($type, $user, $category);

		return Post::create(
			$postData['userId'],
			$postData['categoryId'],
			$postData['title'],
			$postData['type'],
			$postData['content']
		);
	}

	protected static function generatePostData(string $type = null, User $user = null, Category $category = null): array
	{
		$postData['userId'] = empty($user) ? self::generateUser()->getId() : $user->getId();
		$postData['categoryId'] = empty($category) ? self::generateCategory()->getId() : $category->getId();
		$postData['title'] = self::$faker->word;

		if (!empty($type)) {
			if ($type === 'Text') {
				$postData['type'] = 'Text';
				$postData['content'] = self::$faker->paragraph();
			} else {
				$postData['type'] = 'URL';
				$postData['content'] = self::$faker->url;
			}
		} else if (rand(0, 1) === 0) {
			$postData['type'] = 'Text';
			$postData['content'] = self::$faker->paragraph();
		} else {
			$postData['type'] = 'URL';
			$postData['content'] = self::$faker->url;
		}

		return $postData;
	}

	protected static function generateComment(User $user = null, Post $post = null, Comment $reply = null): Comment
	{
		$comment = self::generateCommentData($user, $post, $reply);

		return Comment::create(
			$comment['postId'],
			$comment['userId'],
			$comment['content'],
			$comment['replyId']
		);
	}

	protected static function generateCommentData(User $user = null, Post $post = null, Comment $reply = null): array
	{
		$commentData['userId'] = empty($user) ? self::generateUser()->getId() : $user->getId();
		$commentData['postId'] = empty($post) ? self::generatePost()->getId() : $post->getId();
		$commentData['replyId'] = empty($reply) ? null : $reply->getId();
		$commentData['content'] = self::$faker->paragraph();

		return $commentData;
	}

	public function tearDown(): void
	{
		$tables = ['comment', 'post', 'category', 'user', 'post_vote', 'comment_vote', 'bookmarked_post', 'bookmarked_comment'];
		$database = new Connection();
		$connection = $database->connect();
		$statement = $connection->prepare("SET FOREIGN_KEY_CHECKS = 0");

		$statement->execute();

		foreach ($tables as $table) {
			$statement = $connection->prepare("DELETE FROM `$table`");
			$statement->execute();
			$statement = $connection->prepare("ALTER TABLE `$table` AUTO_INCREMENT = 1");
			$statement->execute();
		}

		$statement = $connection->prepare("SET FOREIGN_KEY_CHECKS = 1");

		$statement->execute();
		$statement->close();

		if (session_status() === PHP_SESSION_ACTIVE) {
			session_unset();
			session_destroy();
		}
	}
}
