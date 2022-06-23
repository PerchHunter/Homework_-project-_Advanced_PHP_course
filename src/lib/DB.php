<?php

class DB {

    const DB_HOST = 'localhost';
    const DB_NAME = 'online_store_advancedphp';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHAR = 'utf8';
    const DB_PORT = 3306;
    const DSN = 'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME . ';charset=' . self::DB_CHAR;

    protected static $instance = null;

    private function __construct() {
        
    }

    private function __clone() {                 
        
    }

    /**
     * @return PDO
     */
    private static function getInstance(): ?PDO {
        if (self::$instance === null) {
            $opt = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => TRUE,
            );
            self::$instance = new PDO(self::DSN, self::DB_USER, self::DB_PASS, $opt);
        }
        return self::$instance;
    }
                                                                                                                                                 

    private static function sql(string $sql, array $args = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }


    public static function select(string $sql, array $args = []): array
    {
        return self::sql($sql, $args)->fetchAll();
    }


    public static function getRow(string $sql, array $args = []): array
    {
        return self::sql($sql, $args)->fetch();
    }


    public static function insert(string $sql, array $args = []): int
    {
        self::sql($sql, $args);
        return self::$instance->lastInsertId();
    }


    public static function update(string $sql, array $args = []): int
    {
        $stmt = self::sql($sql, $args);
        return $stmt->rowCount();
    }


    public static function delete(string $sql, array $args = []): int
    {
        $stmt = self::sql($sql, $args);
        return $stmt->rowCount();
    }

    public static  function transaction(array $data): bool { // [[$sql1,[arg1, arg2]], [$sql2,[arg1, arg2]]]
	    try {
	    	$transaction = true;
		    self::getInstance()->beginTransaction();//обозначаем начало транзакции

		    foreach ($data as $query) {
			   $result = self::sql($query[0], $query[1]);

			   if (!$result) $transaction = false;
		    }

		    if  ($transaction) self::getInstance()->commit(); //если все запросы прошли успешно,- фиксируем
		    else self::getInstance()->rollBack(); //если нет - откатываем

		    return $transaction;
	    } catch (PDOException $e) {
		    self::getInstance()->rollBack();
		    return false;
	    }
    }
}

/*
db::getInstance()->Select(
                'SELECT * FROM goods WHERE category_id = :category AND good_id=:good AND good_is_active=:status',
                ['status' => Status::Active, 'category' => $categoryId, 'good'=>$goodId]);
*/