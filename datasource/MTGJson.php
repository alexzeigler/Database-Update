<?php

/**
 * Class MTGJson
 *
 * Purpose:
 *  Class to generate data from the website mtgjson.com
 *  This data will be in English only.
 *
 */
class MTGJson {
	private $setListUrl = "https://mtgjson.com/api/v5/SetList.json";
	private $sets;
	
	
	public function __construct() {
		echo update::line("<cyan>MTGJson.com</cyan>", null, null, true, true);
		/*
		 * Grabs the set data from MTGJson and sets it to the $sets variable.
		 *
		 * Crashes the program if the data was not found.
		 */
		if (!empty($this->setSets($this->getSetsFromMTGJson())) && !is_null($this->getSets())) {
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
	private function getSetsFromMTGJson() {
		echo update::line("\t<yellow>Getting set information...</yellow>", null, null, false, false);
		$ch = curl_init();
		if ($data = http::get($this->setListUrl, $ch)) {
			$sets = array();
			foreach ($data['data'] as $set) {
				$set['code'] = strtolower($set['code']);
				$sets[strtolower($set['code'])] = $set;
			}
			return $sets;
		} else {
			return null;
		}
	}
	
	/**
	 * @param array|null $sets
	 * @return MTGJson
	 */
	public function setSets(?array $sets): MTGJson {
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