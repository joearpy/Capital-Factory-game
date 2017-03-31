<?php

/**
 * pdo.php
 *
 * DB Class establishes a singleton Database Connection via PDO.
 * Requires that connection constants be defined in a config file somewhere
 * else before calling.
 *
 * Use this class to connect to the Database from anywhere in the app.
 * Assigning $db = DB::getConnection() loads the PDO
 * for running queries.
 *
 * For example:
 * $sql = 'Some SQL Statement';
 * $result = $db->query($sql);
 * returns a PDO result set.
 *
 * Or,
 * $sql = 'INSERT INTO sometable (name) VALUES (:name)';
 * $stmt = $db->prepare($sql);
 * $stmt->bindParam(':name', $name);
 * $stmt->execute();
 * executes a prepared statement to insert $name into 'sometable'.
 *
 * @return PDO Connection 
 */

class DB {

	static private $_db = null; // The same PDO will persist from one call to the next

	private function __construct() {} // disallow calling the class via new DB

	private function __clone() {} // disallow cloning the class

	/**
	 * Establishes a PDO connection if one doesn't exist,
	 * or simply returns the already existing connection.
	 * @return PDO A working PDO connection
	 */
	static public function getConnection($host = false, $dbname = false, $username = false, $password = false) {
		if (self::$_db == null) { // No PDO exists yet, so make one and send it back.
			try {
				self::$_db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8', $username, $password);
				if (APP_ENV != 'production') {
					self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}
			} catch (PDOException $e) {
				// Use next line for debugging only, remove or comment out before going live.
				// echo 'PDO says: ' . $e->getMessage() . '<br />';

				// This is all the end user should see if the connection fails.
				die('<h1>Sorry. The Database connection is temporarily unavailable.</h1>');
			} // end PDO connection try/catch
			return self::$_db;
		} else { // There is already a PDO, so just send it back.
			return self::$_db;
		} // end PDO exists if/else 
	} // end function getConnection
} // end class DB