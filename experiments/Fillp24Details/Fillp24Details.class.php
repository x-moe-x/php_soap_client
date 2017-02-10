<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

class Fillp24Details extends NX_Executable {

	private $p24Data;
	private $storeData;

	public function __construct() {
		parent::__construct(__CLASS__);;
		$this->p24Data = array(
			// PVC A0
			36 => [
				'VK' => 29.90,
			],
			// PVC A0
			37 => [
				'VK' => 24.50,
			],
			// PVC A0
			38 => [
				'VK' => 19.50,
			],
			// PVC A0
			39 => [
				'VK' => 17.50,
			],
			// Kleber A1
			40 => [
				'VK' => 29.90,
			],
			// Kleber A2
			41 => [
				'VK' => 27.50,
			],
			// Fahne A2
			42 => [
				'VK' => 49.50,
			],
		);

		$this->storeData = array();
	}

	public function execute() {
		$dbResult = DBQuery::getInstance()
			->select("SELECT ItemID, PriceID FROM `PriceSets` WHERE ItemId BETWEEN 2137 AND 2670 ");

		while ($current = $dbResult->fetchAssoc()) {
			foreach ($this->p24Data as $avsID => $data) {
				$this->storeData[] = array(
					'ItemID'              => $current['ItemID'],
					'PriceID'             => $current['PriceID'],
					'AttributeValueSetID' => $avsID,
					'Availability'        => 1,
					'PurchasePrice'       => $data['VK'] * 0.72,
					'Oversale'            => 0,
					'UVP'                 => $data['VK'],
				);
			}
		}

		$this->storeToDB();
	}

	private function storeToDB() {
		$countRecords = count($this->storeData);

		if ($countRecords > 0) {
			$this->debug(__FUNCTION__ . " storing $countRecords records of attribute value sets details");
			DBQuery::getInstance()
				->insert("INSERT INTO SetAttributeValueSetsDetails" . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storeData));

		}
	}
}