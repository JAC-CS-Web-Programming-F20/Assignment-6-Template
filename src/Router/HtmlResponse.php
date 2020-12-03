<?php

namespace AssignmentSix\Router;

use AssignmentSix\Views\View;

class HtmlResponse extends Response
{
	private View $view;

	public function __construct()
	{
		$this->addHeader('Content-Type: text/html');
		$this->view = new View();
	}

	public function setResponse(array $data): self
	{
		$data['payload'] = $data['payload'] ?? [];

		if (!empty($data['template'])) {
			$this->view->setTemplate($data['template']);
			$this->view->setData($data);
		}

		if (!empty($data['redirect'])) {
			$this->redirect($data['redirect']);
		}

		if (!empty($data['status'])) {
			$this->setStatusCode($data['status']);
		}

		return $this;
	}

	public function __toString(): string
	{
		$this->printHeaders();

		return $this->view->render();
	}
}
