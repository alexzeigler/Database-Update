<?php
include "datasource/Scryfall.php";
include "datasource/MTGJson.php";
/*
 * Load all files in the "includes" folder.
 */
foreach (glob("includes/*.php") as $filename) {
	include $filename;
}
define('ROOT', realpath(dirname(__FILE__)));

/**
 * Call update to make a new class
 */
new update();

class update {
	private $scryfall;
	private $mtgjson;
	private $masterSets;
	
	/**
	 * update constructor.
	 */
	public function __construct() {
		/*
		 * Create global variables for these objects
		 */
		$this->masterSets = array();
		$this->scryfall = new Scryfall();
		$this->mtgjson = new MTGJson();
		/*
		 * Adds the scryfall set data to the $mastersets variable
		 */
		foreach ($this->scryfall->getSets() as $code => $set) {
			foreach ($set as $key => $value) {
				/*
				 * Does the key exist, after translating?
				 */
				if (isset(Translate::$scryfallTerms[$key]))
					/*
					 * If it is not set in the master, set it from the source data.
					 */
					if (!isset($this->masterSets[$code][$key]))
						$this->masterSets[$code][Translate::$scryfallTerms[$key]] = $set[$key];
				
			}
		}
		/*
		 * Does the exact same thing as above, except for MTGJson data.
		 */
		foreach ($this->mtgjson->getSets() as $code => $set) {
			foreach ($set as $key => $value) {
				/*
				 * Does the key exist, after translating?
				 */
				if (isset(Translate::$mtgJSONTerms[$key]))
					/*
					 * If it is not set in the master, set it from the source data.
					 */
					if (!isset($this->masterSets[strtolower($code)][$key]))
						$this->masterSets[$code][Translate::$mtgJSONTerms[$key]] = $set[$key];
					
					/*
					 * If it does exist, does it match the data already existing?
					 */
					else
						if (trim($this->masterSets[strtolower($code)][$key]) !== trim($set[$key]))
							/*
							 * It does not match; it is a discrepancy.
							 */
							echo update::line("<red>Discrepancy found: </red><cyan>Scryfall[</cyan><yellow>" . Translate::$scryfallTerms[$key] . "</yellow><cyan>] = </cyan><yellow>" . $this->scryfall->getSets()[$code][$key] . "</yellow><red> // </red><cyan>MTGJson[</cyan><yellow>" . Translate::$mtgJSONTerms[$key] . "</yellow><cyan>] = </cyan><yellow>" . $this->mtgjson->getSets()[$code][$key] . "</yellow>", null, null, true);
			}
		}
		$nullableColumns = self::isNullable("sets");
		foreach ($this->masterSets as $code => $set) {
			
			$errorSetKeys = array_diff_key($nullableColumns, $set);
			foreach (array_keys($errorSetKeys) as $errorSetKey){
				if (!boolval($nullableColumns[$errorSetKey]))
					echo update::line("<red>$code has a missing key: $errorSetKey</red>", null, null, true);

			}
		}
		
	}
	
	/**
	 * Print function for the console. This handles colors and other such functions. All credit for this function goes
	 * to https://github.com/nachazo/scryfalldler.
	 * @param string $text
	 * @param null $colorText
	 * @param null $colorBackground
	 * @param bool $addDate
	 * @param bool $breakLine
	 * @return string|null
	 */
	public static function line($text, $colorText = null, $colorBackground = null, $addDate = false, $breakLine = true) {
		$res = null;
		if ($addDate == true) {
			$res .= date("Y-m-d H:i:s") . ":";
		}
		$spaces = null;
		if ($breakLine == true)
			$spaces = " ";
		$res .= (new Colors)->getColoredString($spaces . $text . $spaces, $colorText, $colorBackground);
		if ($breakLine == true)
			$res .= "\n";
		return $res;
	}
	
	/**
	 * Get the column names from a table
	 * @param string $tableName
	 * @return array array
	 */
	private static function getColumns($tableName) {
		return http::searchDB("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='mydb' AND `TABLE_NAME`='" . $tableName . "';", true, "COLUMN_NAME");
	}
	
	private static function isNullable($tableName) {
		return http::searchDB("SELECT `COLUMN_NAME`, if(`IS_NULLABLE` = 'YES', 1, 0) FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='mydb' AND `TABLE_NAME`='" . $tableName . "';", true, "COLUMN_NAME");
		
	}
	
	/**
	 * Get the progress of how close the number is to the finish.
	 * @param int $total
	 * @param int $number
	 * @return int|string
	 */
	private static function get_percentage($total, $number) {
		if ($total > 0) {
			return sprintf("%05.2f", round($number / ($total / 100), 2));
		} else {
			return 0;
		}
	}
}