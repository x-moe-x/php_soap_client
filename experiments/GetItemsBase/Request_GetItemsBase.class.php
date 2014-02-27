<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_GetItemsBase {

	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 * @return PlentySoapRequest_GetItemsBase
	 */
	public function getRequest($lastUpdate, $currentTime, $page) {
		$oPlentySoapRequest_GetItemsBase = new PlentySoapRequest_GetItemsBase();

		// @formatter:off
		fillObjectFromArray($oPlentySoapRequest_GetItemsBase, array(
			'Page' =>					$page,
			'ItemID' =>					null,
			'LastUpdateFrom' =>			$lastUpdate,
			'LastUpdateTill' =>			$currentTime,
			'GetAttributeValueSets' =>	true,
			'GetCategories' =>			false,
			'GetItemAttributeMarkup' =>	false,
			'GetItemOthers' =>			false,
			'GetItemProperties' =>		false,
			'GetItemSuppliers' =>		false,
			'GetLongDescription' =>		false,
			'GetMetaDescription' =>		false,
			'GetShortDescription' =>	false,
			'GetTechnicalData' =>		false,
			'Marking1ID' =>				null,
			'Marking2ID' =>				null,
			'CategoriePath' =>			null,
			'EAN1' =>					null,
			'ExternalItemID' =>			null,
			'ItemNo' =>					null,
			'Lang' =>					null,
			'GetItemURL' =>				null,
			'Gimahhot' =>				null,
			'GoogleProducts' =>			null,
			'Hitmeister' =>				null,
			'Inactive' =>				null,
			'Laary' =>					null,
			'LastInsertedFrom' =>		null,
			'LastInsertedTill' =>		null,
			'MainWarehouseID' =>		null,
			'Moebelprofi' =>			null,
			'ProducerID' =>				null,
			'Referrer' =>				null,
			'Restposten' =>				null,
			'ShopShare' =>				null,
			'Shopgate' =>				null,
			'Shopperella' =>			null,
			'StockAvailable' =>			null,
			'SumoScout' =>				null,
			'Tradoria' =>				null,
			'WebAPI' =>					null,
			'Webshop' =>				null,
			'Yatego' =>					null,
			'Zalando' =>				null
		));
		// @formatter:on

		return $oPlentySoapRequest_GetItemsBase;
	}

}
?>