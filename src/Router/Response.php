<?php

namespace AssignmentSix\Router;

abstract class Response
{
	protected array $headers;
	protected int $statusCode;

	protected function redirect(string $location): void
	{
		$this->addHeader("Location: " . \Url::path($location));
	}

	protected function setStatusCode(int $statusCode): void
	{
		$this->statusCode = $statusCode;
	}

	protected function addHeader(string $header): void
	{
		$this->headers[] = $header;
	}

	protected function printHeaders(): void
	{
		http_response_code($this->statusCode ?? \HttpStatusCode::OK);

		foreach ($this->headers as $header) {
			header($header);
		}
	}

	public abstract function setResponse(array $data): self;
}
