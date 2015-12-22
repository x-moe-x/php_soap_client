<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

/**
 * Class Request_GetItemsBase
 */
class Request_GetItemsBase
{
	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 *
	 * @return PlentySoapRequest_GetItemsBase
	 */
	public static function getRequest($lastUpdate, $currentTime, $page)
	{
		$request = new PlentySoapRequest_GetItemsBase();

		fillObjectFromArray($request, array(
			'Page'                   => $page,
			'ItemID'                 => null,
			'LastUpdateFrom'         => $lastUpdate,
			'LastUpdateTill'         => $currentTime,
			'GetAttributeValueSets'  => true,
			'GetCategories'          => true,
			'GetItemAttributeMarkup' => false,
			'GetItemOthers'          => false,
			'GetItemProperties'      => true,
			'GetItemSuppliers'       => false,
			'GetLongDescription'     => true,
			'GetMetaDescription'     => true,
			'GetShortDescription'    => true,
			'GetTechnicalData'       => true,
			'GetItemURL'             => true,
			'Marking1ID'             => null,
			'Marking2ID'             => null,
			'CategoriePath'          => null,
			'EAN1'                   => null,
			'ExternalItemID'         => null,
			'ItemNo'                 => null,
			'Lang'                   => null,
			'Gimahhot'               => null,
			'GoogleProducts'         => null,
			'Hitmeister'             => null,
			'Inactive'               => null,
			'Laary'                  => null,
			'LastInsertedFrom'       => null,
			'LastInsertedTill'       => null,
			'MainWarehouseID'        => null,
			'Moebelprofi'            => null,
			'ProducerID'             => null,
			'Referrer'               => null,
			'Restposten'             => null,
			'ShopShare'              => null,
			'Shopgate'               => null,
			'Shopperella'            => null,
			'StockAvailable'         => null,
			'SumoScout'              => null,
			'Tradoria'               => null,
			'WebAPI'                 => null,
			'Webshop'                => null,
			'Yatego'                 => null,
			'Zalando'                => null,
			'CouchCommerce'          => null,
			'GetCategoryNames'       => null,
			'Grosshandel'            => null,
			'Hood'                   => null,
			'Otto'                   => null,
			'PlusDe'                 => null,
			'StoreID'                => null,
			'Twenga'                 => null,
			'CallItemsLimit'         => null,
		));

		return $request;
	}
}
