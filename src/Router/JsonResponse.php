<?php

namespace AssignmentSix\Router;

use JsonSerializable;

class JsonResponse extends Response implements JsonSerializable
{
	private string $message;
	private $payload;

	public function __construct()
	{
		$this->addHeader('Content-Type: application/json');
	}

	public function setResponse(array $data): self
	{
		if (!empty($data['status'])) {
			$this->setStatusCode($data['status']);
		}

		$this->message = $data['status'] ?? '';
		$this->message = $data['message'] ?? '';
		$this->payload = $data['payload'] ?? [];

		return $this;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getPayload()
	{
		return $this->payload;
	}

	public function setMessage(string $message): void
	{
		$this->message = $message;
	}

	public function setPayload(string $payload): void
	{
		$this->payload = $payload;
	}

	public function jsonSerialize(): array
	{
		return [
			'message' => $this->message,
			'payload' => $this->payload
		];
	}

	public function __toString(): string
	{
		$this->printHeaders();

		return json_encode($this);
	}
}
