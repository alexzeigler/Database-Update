<?php

/**
 * Class Scryfall
 *
 * Purpose:
 *  Class to generate data from the website scryfall.com
 *  This data will be in English only.
 *
 */
class Scryfall {
	private $baseURL = "https://api.scryfall.com/";
	private $sets;
	
	public function __construct() {
		echo update::line("<cyan>Scryfall.com</cyan>", null, null, true, true);
		if (!empty($this->setSets($this->getSetsFromScryfall())) && !is_null($this->getSets())) {
			echo update::line("<green>Done</green>", null, null, false);
		} else {
			echo update::line("<red>Could not download set data</red>", null, null, false);
			die();
		}
	}
	
	/**
	 * Gets the available sets from scryfall.com and returns them in an array.
	 * @return array|null
	 */
	private function getSetsFromScryfall() {
		echo update::line("\t<yellow>Getting set information...</yellow>", null, null, false, false);
		$ch = curl_init();
		if ($data = http::get($this->baseURL . "sets", $ch)) {
			$sets = array();
			if (isset($data['data'])) {
				foreach ($data['data'] as $set) {
					$sets[$set['code']] = $set;
				}
			} else {
				return null;
			}
			return $sets;
		} else {
			return null;
		}
	}
	
	/**
	 * @param array|null $sets
	 * @return Scryfall
	 */
	public function setSets(?array $sets): Scryfall {
		$this->sets = $sets;
		return $this;
	}
	
	/**
	 * @return mixed
	 */
	public function getSets() {
		return $this->sets;
	}
}