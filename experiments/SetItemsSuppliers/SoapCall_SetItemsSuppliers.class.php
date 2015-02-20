<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetItemsSuppliers.class.php';

/**
 * Class SoapCall_SetItemsSuppliers
 */
class SoapCall_SetItemsSuppliers extends PlentySoapCall
{

	/**
	 * @var int
	 */
	const MAX_SUPPLIERS_PER_PAGES = 50;

	/**
	 * @var SoapCall_SetItemsSuppliers
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__ . ' writing items suppliers data ...');
		try
		{
			// get all values for articles with write permission
			$oDBResult = DBQuery::getInstance()->select($this->getWriteBackQuery());

			// for every 50 ItemIDs ...
			for ($page = 0, $maxPage = ceil($oDBResult->getNumRows() / self::MAX_SUPPLIERS_PER_PAGES); $page < $maxPage; $page++)
			{

				// ... prepare a separate request ...
				$oRequest_SetItemsSuppliers = new RequestContainer_SetItemsSuppliers();

				// ... fill in data
				while (!$oRequest_SetItemsSuppliers->isFull() && ($aCurrentItemsSuppliers = $oDBResult->fetchAssoc()))
				{
					$oRequest_SetItemsSuppliers->add($aCurrentItemsSuppliers);
				}

				// do soap call to plenty
				$response = $this->getPlentySoap()->SetItemsSuppliers($oRequest_SetItemsSuppliers->getRequest());

				// ... if successful ...
				if ($response->Success == true)
				{
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @return string
	 */
	private function getWriteBackQuery()
	{
		return 'SELECT
	ItemSuppliers.ItemID,
	ItemSuppliers.SupplierID,
	ItemSuppliers.ItemSupplierRowID,
	ItemSuppliers.IsRebateAllowed,
	ItemSuppliers.ItemSupplierPrice,
	ItemSuppliers.LastUpdate,
	ItemSuppliers.Priority,
	ItemSuppliers.Rebate,
	ItemSuppliers.SupplierDeliveryTime,
	ItemSuppliers.SupplierItemNumber,
	/* ItemSuppliers.SupplierMinimumPurchase, skipped, use suggestion instead */
	ItemSuppliers.VPE,
	WriteBackSuggestion.SupplierMinimumPurchase
FROM
	`ItemSuppliers`
LEFT JOIN
	`WritePermissions`
ON
	ItemSuppliers.ItemID = WritePermissions.ItemID AND WritePermissions.AttributeValueSetID = 0
LEFT JOIN
	`WriteBackSuggestion`
ON
	ItemSuppliers.ItemID = WriteBackSuggestion.ItemID AND WriteBackSuggestion.AttributeValueSetID = 0
WHERE
	WritePermissions.WritePermission = 1
AND
	WritePermissions.AttributeValueSetID = 0' . PHP_EOL;
	}
}
