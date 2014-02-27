<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SetItemsSuppliers.class.php';

class SoapCall_SetItemsSuppliers extends PlentySoapCall {

	/**
	 * @var int
	 */
	public static $MAX_SUPPLIERS_PER_PAGES = 50;

	/**
	 * @var array TODO remove after debuggun
	 */
	private $aDuplicateMappings = array(416 => 2048, 201 => 2049, 1 => 2050, 298 => 2051);

	/**
	 * @var array
	 */
	private $aMappedItemSuppliers = array();

	/**
	 * @var SoapCall_SetItemsSuppliers
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute() {
		$this -> getLogger() -> debug(__FUNCTION__ . ' writing items suppliers data ...');
		try {
			// get all values for articles with write permission
			$oDBResult = DBQuery::getInstance() -> select($this -> getWriteBackQuery());

			// for every 50 ItemIDs ...
			for ($page = 0, $maxPage = ceil($oDBResult -> getNumRows() / self::$MAX_SUPPLIERS_PER_PAGES); $page < $maxPage; $page++) {

				// ... prepare a separate request ...
				$oRequest_SetItemsSuppliers = new Request_SetItemsSuppliers();

				// TODO remove after debugging start

				// filter for every article wich has a duplicate
				while ($current = $oDBResult -> fetchAssoc()) {
					$itemID = intval($current['ItemID']);
					if (array_key_exists($itemID, $this -> aDuplicateMappings)) {

						// prevent existing records from being overwritten
						$current['ItemSupplierRowID'] = NULL;
						$current['ItemID'] = NULL;

						// set incomplete data in the first place
						$this -> aMappedItemSuppliers[$itemID] = $current;
					}
				}

				$oDBResult2 = DBQuery::getInstance() -> select('SELECT * FROM `ItemSuppliers` WHERE ItemID IN (\'' . implode('\',\'', $this -> aDuplicateMappings) . '\')');
				$aFlippedMappings = array_flip($this -> aDuplicateMappings);
				while ($current = $oDBResult2 -> fetchAssoc()) {
					$itemID = intval($current['ItemID']);

					if (array_key_exists($itemID, $aFlippedMappings) && array_key_exists($aFlippedMappings[$itemID], $this -> aMappedItemSuppliers)) {
						$this -> aMappedItemSuppliers[$aFlippedMappings[$itemID]]['ItemSupplierRowID'] = $current['ItemSupplierRowID'];
						$this -> aMappedItemSuppliers[$aFlippedMappings[$itemID]]['ItemID'] = $current['ItemID'];
					}
				}
				foreach ($this->aMappedItemSuppliers as $aItemsSupplier) {/* @var $aItemsSupplier array  */
					$oRequest_SetItemsSuppliers -> addItemsSupplier($aItemsSupplier);
				}

				// TODO remove after debugging end

			}
			// do soap call to plenty
			$response = $this -> getPlentySoap() -> SetItemsSuppliers($oRequest_SetItemsSuppliers -> getRequest());

			// ... if successful ...
			if ($response -> Success == true) {
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Success');
			} else {

				// ... otherwise log error and try next request
				$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
			}
			$this -> getLogger() -> debug(__FUNCTION__ . ' ... done');
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	/**
	 * @return string
	 */
	private function getWriteBackQuery() {
		return 'SELECT
	ItemSuppliers.*
FROM
	`ItemSuppliers`
LEFT JOIN
	`WritePermissions`
ON
	ItemSuppliers.ItemID = WritePermissions.ItemID
WHERE
	WritePermissions.AttributeValueSetID = 0
AND
	WritePermissions.WritePermission = 1' . PHP_EOL;
	}

}
