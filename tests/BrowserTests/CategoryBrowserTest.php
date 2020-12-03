<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSix\Models\Category;
use AssignmentSixTests\BrowserTests\BrowserTest;

final class CategoryBrowserTest extends BrowserTest
{
	public function testCategoryWasCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$categoryData = $this->generateCategoryData($user);

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');

		$h1 = $this->findElement("h1");
		$createdByInput = $this->findElement("form#new-category-form input[name=\"createdBy\"][type=\"hidden\"]");
		$titleInput = $this->findElement("form#new-category-form input[name=\"title\"]");
		$descriptionInput = $this->findElement("form#new-category-form input[name=\"description\"]");
		$submitButton = $this->findElement("form#new-category-form button");

		$this->assertStringContainsString("Welcome", $h1->getText());
		$this->assertEquals($user->getId(), $createdByInput->getAttribute('value'));

		$titleInput->sendKeys($categoryData["title"]);
		$descriptionInput->sendKeys($categoryData["description"]);
		$submitButton->click();

		$category = Category::findByTitle($categoryData['title']);
		$categoryId = $category->getId();
		$categoryElement = $this->findElement("tr[category-id=\"$categoryId\"]");

		$this->assertStringContainsString($categoryData["title"], $categoryElement->getText());
		$this->assertStringContainsString($category->getCreatedAt(), $categoryElement->getText());
		$this->assertStringContainsString($category->getCreatedBy()->getUsername(), $categoryElement->getText());
		$this->assertStringContainsString("No", $categoryElement->getText());

		$this->logOut();
	}

	public function testManyCategoriesWereCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');

		for ($i = 0; $i < rand(2, 5); $i++) {
			$categoryData = $this->generateCategoryData($user);

			$createdByInput = $this->findElement("form#new-category-form input[name=\"createdBy\"][type=\"hidden\"]");
			$titleInput = $this->findElement("form#new-category-form input[name=\"title\"]");
			$descriptionInput = $this->findElement("form#new-category-form input[name=\"description\"]");
			$submitButton = $this->findElement("form#new-category-form button");

			$this->assertEquals($user->getId(), $createdByInput->getAttribute('value'));

			$titleInput->sendKeys($categoryData["title"]);

			$descriptionInput->sendKeys($categoryData["description"]);

			$submitButton->click();

			$category = Category::findByTitle($categoryData['title']);
			$categoryId = $category->getId();
			$categoryElement = $this->findElement("tr[category-id=\"$categoryId\"]");

			$this->assertStringContainsString($categoryData["title"], $categoryElement->getText());
			$this->assertStringContainsString($category->getCreatedAt(), $categoryElement->getText());
			$this->assertStringContainsString($category->getCreatedBy()->getUsername(), $categoryElement->getText());
			$this->assertStringContainsString("No", $categoryElement->getText());
		}

		$this->logOut();
	}

	/**
	 * @dataProvider createCategoryProvider
	 */
	public function testCategoryWasNotCreated(array $categoryData, string $message, bool $generateCategory = false): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));

		if ($generateCategory) {
			$this->generateCategory($user, "Pokemon", "The best Pokemon community!");
		}

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');

		$titleInput = $this->findElement("form#new-category-form input[name=\"title\"]");
		$descriptionInput = $this->findElement("form#new-category-form input[name=\"description\"]");
		$submitButton = $this->findElement("form#new-category-form button");

		$titleInput->sendKeys($categoryData["title"]);
		$descriptionInput->sendKeys($categoryData["description"]);
		$submitButton->click();

		$h1 = $this->findElement("h1");
		$body = $this->findElement("body");

		$this->assertStringContainsString("Error", $h1->getText());
		$this->assertStringContainsString($message, $body->getText());

		$this->logOut();
	}

	public function createCategoryProvider()
	{
		yield "blank title" => [
			[
				"title" => "",
				"description" => "The best Pokemon community!"
			],
			"Cannot create Category: Missing title."
		];

		yield "duplicate title" => [
			[
				"title" => "Pokemon",
				"description" => "The best Pokemon community!"
			],
			"Cannot create Category: Title already exists.",
			true
		];
	}

	public function testCreateCategoryInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$this->goTo('/');
		$this->assertFalse($this->doesElementExist("form#new-category-form"));
	}

	public function testCategoryWasFoundById(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$categoryTitle = $this->findElement("#category-title");
		$categoryDescription = $this->findElement("#category-description");

		$this->assertStringContainsString($category->getTitle(), $categoryTitle->getText());
		$this->assertStringContainsString($category->getDescription(), $categoryDescription->getText());
	}

	public function testCategoryWasNotFoundByWrongId(): void
	{
		$randomCategoryId = rand(1, 100);
		$this->goTo('category/{id}', ['id' => $randomCategoryId]);

		$h1 = $this->findElement("h1");
		$body = $this->findElement("body");

		$this->assertStringContainsString("Error", $h1->getText());
		$this->assertStringContainsString("Cannot find Category: Category does not exist with ID $randomCategoryId.", $body->getText());
	}

	public function testCategoryWasUpdatedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);
		$categoryId = $category->getId();
		$newCategoryData = $this->generateCategoryData();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('category/{id}/edit', ['id' => $categoryId]);

		$titleInput = $this->findElement("form#edit-category-form input[name=\"title\"]");
		$descriptionInput = $this->findElement("form#edit-category-form input[name=\"description\"]");
		$submitButton = $this->findElement("form#edit-category-form button");

		$this->assertStringContainsString($category->getTitle(), $titleInput->getAttribute("value"));
		$this->assertStringContainsString($category->getDescription(), $descriptionInput->getAttribute("value"));

		$titleInput->sendKeys($newCategoryData["title"]);
		$descriptionInput->sendKeys($newCategoryData["description"]);
		$submitButton->click();

		$categoryTitle = $this->findElement("#category-title");
		$categoryDescription = $this->findElement("#category-description");

		$this->assertStringContainsString($newCategoryData["title"], $categoryTitle->getText());
		$this->assertStringContainsString($newCategoryData["description"], $categoryDescription->getText());

		$this->logOut();
	}

	public function testCategoryWasNotUpdatedWithBlankTitle(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);
		$categoryId = $category->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnLink('category/{id}/edit', ['id' => $categoryId]);

		$titleInput = $this->findElement("form#edit-category-form input[name=\"title\"]");
		$submitButton = $this->findElement("form#edit-category-form button");

		$this->assertStringContainsString($category->getTitle(), $titleInput->getAttribute("value"));

		$titleInput->clear();
		$submitButton->click();

		$h1 = $this->findElement("h1");
		$body = $this->findElement("body");

		$this->assertStringContainsString("Error", $h1->getText());
		$this->assertStringContainsString("Cannot edit Category: Missing title.", $body->getText());

		$this->logOut();
	}

	public function testUpdateCategoryInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$categoryTitle = $this->findElement("#category-title");
		$categoryDescription = $this->findElement("#category-description");

		$this->assertStringContainsString($category->getTitle(), $categoryTitle->getText());
		$this->assertStringContainsString($category->getDescription(), $categoryDescription->getText());
		$this->assertFalse($this->doesElementExist("a[href*=\"" . \Url::path('category/{id}/edit', ['id' => $categoryId]) . "\"]"));
	}

	public function testCategoryWasDeletedSuccessfully(): void
	{
		$userData = $this->generateUserData();
		$user = $this->generateUser(...array_values($userData));
		$category = $this->generateCategory($user);
		$categoryId = $category->getId();

		$this->logIn($userData['email'], $userData['password']);
		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);
		$this->clickOnButton("form#delete-category-form button");

		$categoriesRow = $this->findElement("tr[category-id=\"$categoryId\"]");

		$this->assertStringContainsString($category->getTitle(), $categoriesRow->getText());
		$this->assertStringContainsString($category->getCreatedBy()->getUsername(), $categoriesRow->getText());
		$this->assertStringContainsString("Yes", $categoriesRow->getText());

		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$this->assertFalse($this->doesElementExist("form#edit-category-form"));
		$this->assertFalse($this->doesElementExist("form#delete-category-form"));
		$this->assertFalse($this->doesElementExist("form#new-post-form"));

		$this->logOut();
	}

	public function testDeleteCategoryInterfaceNotVisibleWhenNotLoggedIn(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		$this->goTo('/');
		$this->clickOnLink('category/{id}', ['id' => $categoryId]);

		$categoryTitle = $this->findElement("#category-title");
		$categoryDescription = $this->findElement("#category-description");

		$this->assertStringContainsString($category->getTitle(), $categoryTitle->getText());
		$this->assertStringContainsString($category->getDescription(), $categoryDescription->getText());
		$this->assertFalse($this->doesElementExist("form#delete-category-form"));
	}
}
