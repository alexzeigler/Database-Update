<?php

class http {
	/**
	 * Generate GUID V4
	 * @param bool $trim
	 * @return string
	 */
	public static function create_guid($trim = true) { // Create GUID (Globally Unique Identifier)
		// Windows
		if (function_exists('com_create_guid') === true) {
			if ($trim === true)
				return trim(com_create_guid(), '{}');
			else
				return com_create_guid();
		}
		
		// OSX/Linux
		if (function_exists('openssl_random_pseudo_bytes') === true) {
			$data = openssl_random_pseudo_bytes(16);
			$data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
			return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
		}
		
		// Fallback (PHP 4.2+)
		mt_srand((double)microtime() * 10000);
		$charid = strtolower(md5(uniqid(rand(), true)));
		$hyphen = chr(45);                  // "-"
		$lbrace = $trim ? "" : chr(123);    // "{"
		$rbrace = $trim ? "" : chr(125);    // "}"
		$guidv4 = $lbrace .
			substr($charid, 0, 8) . $hyphen .
			substr($charid, 8, 4) . $hyphen .
			substr($charid, 12, 4) . $hyphen .
			substr($charid, 16, 4) . $hyphen .
			substr($charid, 20, 12) .
			$rbrace;
		return $guidv4;
	}
	
	/**
	 * Connect to the database and return it as a PDO object.
	 * @param string $db Schema to search through.
	 * @return PDO|null
	 */
	public static function connectDB($db) {
		$conn = null;
		/*
		 * Grab the config of the database stored in config.php
		 */
		include_once ROOT . "/config/config.php";
		$config = new config();
		/*
		 * Set the options and the dsn for the PDO object
		 */
		$dsn = "mysql:host=" . $config->host. ";dbname=$db;charset=" . $config->charset;
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		];
		/*
		 * Try to connect the database.
		 */
		try {
			$conn = new PDO($dsn, $config->user, $config->pass, $options);
		} catch (PDOException $e) {
			/*
			 * Kill the program if a connection failed.
			 */
			echo update::line("<red>ERROR: Could not connect database</red>", null, null, true);
			echo update::line("<red>" . str_replace("\n", "\n\t", $e) . "</red>", null, null, false, true);
			die();
		}
		return $conn;
	}
	
	/**
	 * Take a query and search the database.
	 * @param string $sql The string to use for searching the DB
	 * @param bool $assoc
	 * @param null $assocVal
	 * @param string $db Optional string to set the schema of the DB
	 * @param bool $combine
	 * @return array $returnArray
	 * @see http::connectDB()       For the method of connection to the database
	 */
	public static function searchDB($sql, $assoc = null, $assocVal = null, $db = 'mydb', $combine = true) {
		$conn = http::connectDB($db);
		$stmt = null;
		try {
			$stmt = $conn->query($sql);
		} catch (PDOException $e) {
			echo update::line("<red>ERROR: There is an error in the SQL statement: </red><cyan>$sql</cyan>", null, null, true);
			echo update::line("<red>" . str_replace("\n", "\n\t", $e) . "</red>", null, null, false, true);
		}
		$returnArray = array();
		if ($stmt) {
			if ($stmt->rowCount() > 0) {
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($assoc && $assocVal) {
						/*
						 * Unset the variable in the array of the associated value. This is to avoid duplication.
						 */
						$value = $row[$assocVal];
						unset($row[$assocVal]);
						/*
						 * Store the count to avoid recounting.
						 */
						$rowCount = count($row);
						/*
						 * If there is only one value remaining the array will be combined and stored
						 * Example:
						 *      $row = array( "IS_NULLABLE" => "YES")
						 *  will be converted to:
						 *      $row = "YES"
						 *
						 */
						if ($combine)
							if ($rowCount == 1)
								$row = implode("", $row);
						/*
						 * If the array is empty, remove the array and just add the value into the return array based on the
						 * associated value.
						 *
						 */
						if ($rowCount == 0)
							$returnArray[] = $value;
						/*
						 * Default behavior of the return. Throw everything under the associated value.
						 */
						else
							$returnArray[$value] = $row;
					}
					if ($assoc && !$assocVal)
						echo update::line("<yellow>WARNING: Associative value needed</yellow>", null, null, true);
					if (!$assoc && !$assocVal) {
						/*
						 * If there is only one value remaining the array will be combined and stored
						 * Example:
						 *      $row = array( "IS_NULLABLE" => "YES")
						 *  will be converted to:
						 *      $row = "YES"
						 *
						 */
						if ($combine)
							if (count($row) == 1)
								$row = implode("", $row);
						/*
						 * Default behavior of the return.
						 */
						$returnArray[] = $row;
					}
				}
			} else {
				/*
				 * The SQL Query returned no results
				 */
				echo update::line("<yellow>WARNING: Query </yellow><cyan>$sql</cyan><yellow> did not return any results.</yellow>", null, null, true);
				
			}
		}
		return $returnArray;
	}
	
	/**
	 * Reach out to the specified route.
	 * @param string $route
	 * @param resource $ch
	 * @param bool $json_decoded
	 * @return bool|mixed|string
	 */
	public static function get($route, $ch, $json_decoded = true) {
		global $proxy;
		if (!$ch)
			return false;
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL, $route);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if ($proxy)
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		$res = curl_exec($ch);
		if ($res) {
			if ($json_decoded == true)
				$res = json_decode($res, true);
			return $res;
		} else {
			return false;
		}
	}
}
