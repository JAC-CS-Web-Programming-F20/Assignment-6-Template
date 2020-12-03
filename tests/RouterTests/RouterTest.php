<?php

namespace AssignmentSixTests\RouterTests;

use AssignmentSixTests\AssignmentSixTest;
use Faker\Factory;
use GuzzleHttp\Client;

class RouterTest extends AssignmentSixTest
{
	protected static Client $client;

	public static function setUpBeforeClass(): void
	{
		self::$faker = Factory::create();
	}

	public function setUp(): void
	{
		self::$client = new Client([
			'base_uri' => \Url::base(),
			'headers' => ['Accept' => 'application/json'],
			'cookies' => true,
			'http_errors' => false
		]);
	}

	protected function getResponse(string $url = '', string $method = 'GET', array $data = [], bool $isJson = true)
	{
		$request = $this->buildRequest($method, $url, $data);
		$response = self::$client->request(
			$request['method'],
			$request['url'],
			$request['body']
		);
		$status = $response->getStatusCode();
		$body = $response->getBody();
		$jsonResponse = json_decode($body, true);
		$jsonResponse['status'] = $status;

		return $jsonResponse;
	}

	protected function buildRequest(string $method, string $url, array $data): array
	{
		$body['form_params'] = [];

		foreach ($data as $key => $value) {
			$body['form_params'][$key] = $value;
		}

		return [
			'method' => $method,
			'url' => $url,
			'body' => $body
		];
	}
}
