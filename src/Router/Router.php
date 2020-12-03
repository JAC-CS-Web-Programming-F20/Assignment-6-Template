<?php

namespace AssignmentSix\Router;

use AssignmentSix\Controllers\Controller;
use AssignmentSix\Router\{Request, Response};
use Exception;
use ReflectionClass;

class Router
{
	private Request $request;
	private Response $response;
	public Controller $controller;
	private const CONTROLLER_NAMESPACE = 'AssignmentSix\\Controllers\\';

	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
		$this->setController($this->request->getController());
	}

	private function setController(string $controllerName): void
	{
		if (empty($controllerName)) {
			$controllerName = 'Home';
		}

		$controllerName = self::CONTROLLER_NAMESPACE . ucfirst($controllerName) . 'Controller';

		if (!class_exists($controllerName)) {
			$controllerName = self::CONTROLLER_NAMESPACE . 'ErrorController';
		}

		$reflection_class = new ReflectionClass($controllerName);
		$this->controller = $reflection_class->newInstanceArgs([$this->request, $this->response]);
	}

	public function dispatch(): Response
	{
		try {
			$this->response = $this->controller->doAction();
		} catch (Exception $exception) {
			return $this->response->setResponse([
				'status' => \HttpStatusCode::BAD_REQUEST,
				'template' => "ErrorView",
				'title' => "Error!",
				'message' => $exception->getMessage()
			]);
		}

		return $this->response;
	}
}
