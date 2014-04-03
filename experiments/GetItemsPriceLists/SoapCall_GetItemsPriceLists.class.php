<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsPriceLists.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

class SoapCall_GetItemsPriceLists extends PlentySoapCall {

	/**
	 * @var int
	 */
	const MAX_PRICE_SETS_PER_PAGE = 200;

	/**
	 * @var array
	 */
	private $aPriceSets;

	/**
	 * @return SoapCall_GetItemsPriceLists
	 */
	public function __construct() {
		parent::__construct(__CLASS__);

		$this -> aPriceSets = array();

		DBQuery::getInstance() -> truncate('TRUNCATE TABLE `PriceSets`');
	}

	/**
	 * @return void
	 */
	public function execute() {
		try {
			// get all possible Item Variants
			$result = DBQuery::getInstance() -> select($this -> getQuery());

			// for every 200 variants ...
			for ($page = 0, $maxPage = ceil($result -> getNumRows() / self::MAX_PRICE_SETS_PER_PAGE); $page < $maxPage; $page++) {

				// ... prepare a seperate request
				$oRequest_GetItemsPriceLists = new Request_GetItemsPriceLists();
				while (!$oRequest_GetItemsPriceLists -> isFull() && $current = $result -> fetchAssoc()) {
					$oRequest_GetItemsPriceLists -> addArticleVariant($current['ItemID'], $current['AttributeValueSetID']);
				}

				// ... then do the soap call ...
				$response = $this -> getPlentySoap() -> GetItemsPriceLists($oRequest_GetItemsPriceLists -> getRequest());

				// ... if successfull ...
				if ($response -> Success == true) {

					// ... then process response

					$this -> responseInterpretation($response);
				} else {

					// ... otherwise log error and try next request
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}

			}

			// when done store all retrieved data to db
			$this -> storeToDB();

		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	/**
	 * @param PlentySoapResponse_GetItemsPriceLists $oPlentySoapResponse_GetItemsPriceLists
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsPriceLists $oPlentySoapResponse_GetItemsPriceLists) {
		if (is_array($oPlentySoapResponse_GetItemsPriceLists -> ItemsPriceList -> item)) {
			foreach ($oPlentySoapResponse_GetItemsPriceLists -> ItemsPriceList -> item as $oPlentySoapResponseObject_GetItemsPriceLists) {
				/* @var $oPlentySoapResponseObject_GetItemsPriceLists PlentySoapResponseObject_GetItemsPriceLists */

				if (is_array($oPlentySoapResponseObject_GetItemsPriceLists -> ItemPriceSets -> item)) {
					foreach ($oPlentySoapResponseObject_GetItemsPriceLists -> ItemPriceSets -> item as $oPlentySoapObject_ItemPriceSet) {
						/* @var $oPlentySoapObject_ItemPriceSet PlentySoapObject_ItemPriceSet */
						$this -> processPriceSet($oPlentySoapResponseObject_GetItemsPriceLists -> ItemID, $oPlentySoapObject_ItemPriceSet);
					}
				} else {
					$this -> processPriceSet($oPlentySoapResponseObject_GetItemsPriceLists -> ItemID, $oPlentySoapResponseObject_GetItemsPriceLists -> ItemPriceSets -> item);
				}
			}
		} else {
			if (is_array($oPlentySoapResponse_GetItemsPriceLists -> ItemsPriceList -> item -> ItemPriceSets -> item)) {
				foreach ($oPlentySoapResponse_GetItemsPriceLists -> ItemsPriceList -> item -> ItemPriceSets -> item as $oPlentySoapObject_ItemPriceSet) {
					/* @var $oPlentySoapObject_ItemPriceSet PlentySoapObject_ItemPriceSet */
					$this -> processPriceSet($oPlentySoapResponse_GetItemsPriceLists -> ItemsPriceList -> item -> ItemID, $oPlentySoapObject_ItemPriceSet);
				}
			} else {
				$this -> processPriceSet($oPlentySoapResponse_GetItemsPriceLists -> ItemsPriceList -> item -> ItemID, $oPlentySoapResponse_GetItemsPriceLists -> ItemsPriceList -> item -> ItemPriceSets -> item);
			}
		}
	}

	/**
	 * @return void
	 */
	private function storeToDB() {
		$priceSetCount = count($this -> aPriceSets);
		if ($priceSetCount > 0) {
			$this -> getLogger() -> info(__FUNCTION__ . " storing $priceSetCount price sets");
			DBQuery::getInstance() -> insert('INSERT INTO `PriceSets`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this -> aPriceSets));
		}
	}

	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemPriceSet $oPlentySoapObject_ItemPriceSet
	 * @return void
	 */
	private function processPriceSet($itemID, $oPlentySoapObject_ItemPriceSet) {
		// check if price-set is new ...
		if (!array_key_exists($oPlentySoapObject_ItemPriceSet -> PriceID, $this -> aPriceSets)) {
			// ... then store data

			// @formatter:off
			$this->aPriceSets[$oPlentySoapObject_ItemPriceSet->PriceID] = array(
				'ItemID' =>				$itemID,
				'PriceID' =>			$oPlentySoapObject_ItemPriceSet -> PriceID,
				'Price' =>				$oPlentySoapObject_ItemPriceSet -> Price,
				'Price1' =>				$oPlentySoapObject_ItemPriceSet -> Price1,
				'Price2' =>				$oPlentySoapObject_ItemPriceSet -> Price2,
				'Price3' =>				$oPlentySoapObject_ItemPriceSet -> Price3,
				'Price4' =>				$oPlentySoapObject_ItemPriceSet -> Price4,
				'Price5' =>				$oPlentySoapObject_ItemPriceSet -> Price5,
				'Price6' =>				$oPlentySoapObject_ItemPriceSet -> Price6,
				'Price7' =>				$oPlentySoapObject_ItemPriceSet -> Price7,
				'Price8' =>				$oPlentySoapObject_ItemPriceSet -> Price8,
				'Price9' =>				$oPlentySoapObject_ItemPriceSet -> Price9,
				'Price10' =>			$oPlentySoapObject_ItemPriceSet -> Price10,
				'Price11' =>			$oPlentySoapObject_ItemPriceSet -> Price11,
				'Price12' =>			$oPlentySoapObject_ItemPriceSet -> Price12,
				'Lot' =>				$oPlentySoapObject_ItemPriceSet -> Lot,
				'Package' =>			$oPlentySoapObject_ItemPriceSet -> Package,
				'PackagingUnit' =>		$oPlentySoapObject_ItemPriceSet -> PackagingUnit,
				'Position' =>			$oPlentySoapObject_ItemPriceSet -> Position,
				'PurchasePriceNet' =>	$oPlentySoapObject_ItemPriceSet -> PurchasePriceNet,
				'RRP' =>				$oPlentySoapObject_ItemPriceSet -> RRP,
				'RebateLevelPrice10' => $oPlentySoapObject_ItemPriceSet -> RebateLevelPrice10,
				'RebateLevelPrice11' => $oPlentySoapObject_ItemPriceSet -> RebateLevelPrice11,
				'RebateLevelPrice6' =>	$oPlentySoapObject_ItemPriceSet -> RebateLevelPrice6,
				'RebateLevelPrice7' =>	$oPlentySoapObject_ItemPriceSet -> RebateLevelPrice7,
				'RebateLevelPrice8' =>	$oPlentySoapObject_ItemPriceSet -> RebateLevelPrice8,
				'RebateLevelPrice9' =>	$oPlentySoapObject_ItemPriceSet -> RebateLevelPrice9,
				'ShowOnly' =>			$oPlentySoapObject_ItemPriceSet -> ShowOnly,
				'TypeOfPackage' =>		$oPlentySoapObject_ItemPriceSet -> TypeOfPackage,
				'Unit' =>				$oPlentySoapObject_ItemPriceSet -> Unit,
				'Unit1' =>				$oPlentySoapObject_ItemPriceSet -> Unit1,
				'Unit2' =>				$oPlentySoapObject_ItemPriceSet -> Unit2,
				'UnitLoadDevice' =>		$oPlentySoapObject_ItemPriceSet -> UnitLoadDevice,
				'VAT' =>				$oPlentySoapObject_ItemPriceSet -> VAT,
				'WeightInGramm' =>		$oPlentySoapObject_ItemPriceSet -> WeightInGramm,
				'HeightInMM' =>			$oPlentySoapObject_ItemPriceSet -> HeightInMM,
				'LengthInMM' =>			$oPlentySoapObject_ItemPriceSet -> LengthInMM,
				'WidthInMM' =>			$oPlentySoapObject_ItemPriceSet -> WidthInMM
			);
			// @formatter:on
		} else {
			// ... otherwise skip
		}
	}

	/**
	 * @return string query
	 */
	private function getQuery() {
		return 'Select
	ItemsBase.ItemID,
	CASE WHEN (AttributeValueSets.AttributeValueSetID IS null) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END AttributeValueSetID
FROM
	ItemsBase
LEFT JOIN AttributeValueSets
    ON ItemsBase.ItemID = AttributeValueSets.ItemID
ORDER BY
	ItemsBase.ItemID';
	}

}
?>