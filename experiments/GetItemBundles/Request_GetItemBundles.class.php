<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

/**
 * Class Request_GetItemBundles
 */
class Request_GetItemBundles {
	/**
	 * @param int $lastUpdate
	 * @param int $page
	 *
	 * @return PlentySoapRequest_GetItemBundles
	 */
	public static function getRequest($lastUpdate, $page) {
		$request = new PlentySoapRequest_GetItemBundles();

		fillObjectFromArray($request, array(
			'Page'            => $page,
			'LastUpdate'      => $lastUpdate,
			'LastInserted'    => null,
			'BundleSKU'       => null,
			'CallItemsLimit'  => null,
			'CouchCommerce'   => null,
			'Gimahhot'        => null,
			'GoogleProducts'  => null,
			'Grosshandel'     => null,
			'Hitmeister'      => null,
			'Hood'            => null,
			'ItemSKU'         => null,
			'Laary'           => null,
			'Lang'            => null,
			'MainWarehouseID' => null,
			'Marking1ID'      => null,
			'Marking2ID'      => null,
			'Moebelprofi'     => null,
			'Otto'            => null,
			'PlusDe'          => null,
			'ProducerID'      => null,
			'Restposten'      => null,
			'ShopShare'       => null,
			'Shopgate'        => null,
			'Shopperella'     => null,
			'StockAvailable'  => null,
			'SumoScout'       => null,
			'Tradoria'        => null,
			'Twenga'          => null,
			'WebAPI'          => null,
			'Webshop'         => null,
			'Yatego'          => null,
			'Zalando'         => null,
		));

		return $request;
	}
}
