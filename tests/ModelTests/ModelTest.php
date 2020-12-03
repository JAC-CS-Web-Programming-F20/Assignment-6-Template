<?php

namespace AssignmentSixTests\ModelTests;

use AssignmentSixTests\AssignmentSixTest;
use Faker\Factory;

class ModelTest extends AssignmentSixTest
{
	public static function setUpBeforeClass(): void
	{
		self::$faker = Factory::create();
	}
}
