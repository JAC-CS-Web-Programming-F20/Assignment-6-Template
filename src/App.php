<?php

namespace AssignmentSix;

use AssignmentSix\Router\{
	HtmlResponse,
	JsonResponse,
	Response,
	Request,
	Router
};

class App
{
	private Request $request;
	private Response $response;
	private Router $router;

	public function __construct()
	{
		\Session::start();

		$requestMethod = $_POST['method'] ?? $_SERVER['REQUEST_METHOD'];
		$queryString = $_SERVER['QUERY_STRING'];
		$parameters = $this->getParameters($requestMethod);

		$this->request = new Request($requestMethod, $queryString, $parameters);
		$this->response = $this->getResponse(apache_request_headers()['Accept']);
		$this->router = new Router($this->request, $this->response);
	}

	private function getParameters($requestMethod): array
	{
		switch ($requestMethod) {
			case 'POST':
				$parameters = $_POST;
				break;
			case 'PUT':
				parse_str(file_get_contents("php://input"), $parameters);
				break;
			default:
				return $parameters = [];
		}

		return $parameters;
	}

	private function getResponse($responseType): Response
	{
		switch ($responseType) {
			case 'application/json':
				$response = new JsonResponse();
				break;
			default:
				$response = new HtmlResponse();
				break;
		}

		return $response;
	}

	public function dispatch(): void
	{
		print $this->router->dispatch();
	}
}
