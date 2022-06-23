<?php
	require_once '../../vendor/autoload.php';
	require_once '../configurations/autoload.php';

	use PHPUnit\DbUnit\TestCaseTrait;

	class M_CartTest extends BaseTest {

		use TestCaseTrait;

		/**
		 * @return PHPUnit\DbUnit\Database\Connection
		 */
		public function getConnection()
		{
			$pdo = new PDO('mysqli::memory:');
			return $this->createDefaultDBConnection($pdo, ':memory:');
		}

		/**
		 * @return PHPUnit\DbUnit\DataSet\IDataSet
		 */
		public function getDataSet()
		{
			return $this->createFlatXMLDataSet(dirname(__FILE__).'/data/online_store_advancedphp.xml');
		}

	}
