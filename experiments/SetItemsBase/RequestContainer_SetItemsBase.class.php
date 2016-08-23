<?php
require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemsBase
 */
class RequestContainer_SetItemsBase extends RequestContainer
{

	/**
	 * @return RequestContainer_SetItemsBase
	 */
	public function __construct()
	{
		parent::__construct(SoapCall_SetItemsBase::MAX_ITEMS_PER_PAGES);
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetItemsBase
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_SetItemsBase();

		$request->BaseItems = new ArrayOfPlentysoapobject_setitemsbaseitembase();
		$request->BaseItems->item = array();

		foreach ($this->items as $item)
		{
			$itemBase = new PlentySoapObject_SetItemsBaseItemBase();
			fillObjectFromArray($itemBase, array(
				'ItemID'              => $item['ItemID'],
				'ItemNo'              => $item['ItemNo'],
				'EAN1'                => $item['EAN1'],
				'EAN2'                => $item['EAN2'],
				'EAN3'                => $item['EAN3'],
				'EAN4'                => $item['EAN4'],
				'ISBN'                => $item['ISBN'],
				'ExternalItemID'      => $item['ExternalItemID'],
				'Condition'           => $item['Condition'],
				'CustomsTariffNumber' => $item['CustomsTariffNumber'],
				'FSK'                 => $item['FSK'],
				'Marking1ID'          => $item['Marking1ID'],
				'Marking2ID'          => $item['Marking2ID'],
				'Model'               => $item['Model'],
				'Position'            => $item['Position'],
				'ProducerID'          => $item['ProducerID'],
				'ProducingCountryID'  => $item['ProducingCountryID'],
				'Published'           => $item['Published'],
				'Type'                => $item['Type'],
				'VATInternalID'       => $item['VATInternalID'],
				'WebShopSpecial'      => $item['WebShopSpecial'],
				'DeleteStoreIDs'      => $item['DeleteStoreIDs'],
			));

			// workaround to change the articles position
			$itemBase->Others = new PlentySoapObject_ItemOthers();
			$itemBase->Others->Position = $item['Others_Position'];

			$request->BaseItems->item[] = $itemBase;
		}
		return $request;
	}
}
