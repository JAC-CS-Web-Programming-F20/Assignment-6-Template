<?php

namespace AssignmentSix\Exceptions;

use Exception;

class PostException extends Exception
{
	public function __construct($message)
	{
		parent::__construct($message);
		error_log($this);
	}

	public function __toString()
	{
		return self::class . ": {$this->message}\n{$this->getFile()} @ Line {$this->getLine()}\n";
	}
}
