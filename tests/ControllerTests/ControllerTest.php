<?php

namespace AssignmentSixTests\ControllerTests;

use AssignmentSix\Controllers\AuthController;
use AssignmentSix\Controllers\Controller;
use AssignmentSix\Router\JsonResponse;
use AssignmentSix\Router\Request;
use AssignmentSixTests\AssignmentSixTest;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;

class ControllerTest extends AssignmentSixTest
{
	public static function setUpBeforeClass(): void
	{
		self::$faker = Factory::create();
	}

	public function setUp(): void
	{
		session_start();
	}

	protected function login(string $email, string $password): void
	{
		$request = $this->createMockRequest(
			['login'],
			'POST',
			[
				'email' => $email,
				'password' => $password
			]
		);

		/** @var Request $request */
		$controller = new AuthController($request, new JsonResponse());

		$controller->doAction();
	}

	protected function createMockRequest(array $headerData = [], string $requestMethod = 'GET', array $bodyData = []): MockObject
	{
		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn([
			'body' => $bodyData,
			'header' => $headerData
		]);

		return $request;
	}

	protected function wasExceptionThrown(string $exception, string $message, Controller $controller, string $method): bool
	{
		try {
			call_user_func([$controller, $method]);
		} catch (\Exception $caughtException) {
			$this->assertInstanceOf($exception, $caughtException);
			$this->assertEquals($message, $caughtException->getMessage());
			return true;
		}

		return false;
	}
}
