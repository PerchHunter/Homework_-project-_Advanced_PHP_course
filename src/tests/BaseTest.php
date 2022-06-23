<?php
	use PHPUnit\Framework\TestCase;


	abstract class BaseTest extends Testcase{
		protected function setUp(): void
		{
			App::Init();
		}

	}
