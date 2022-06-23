<?php
	require_once '../configurations/autoload.php';

	class DBTest extends BaseTest {

		use TestCaseTrait;

		// инстанцировать pdo только один раз во время выполнения тестов для очистки/загрузки фикстуры
		static private $pdo = null;

		// инстанцировать только PHPUnit\DbUnit\Database\Connection один раз во время теста
		private $connect = null;

		final public function getConnection()
		{
			if ($this->connect === null) {
				if (self::$pdo === null) {
					self::$pdo = new PDO( $GLOBALS['DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS'] );
				}
				$this->connect = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_HOST']);
			}

			return $this->connect;
		}
	}
