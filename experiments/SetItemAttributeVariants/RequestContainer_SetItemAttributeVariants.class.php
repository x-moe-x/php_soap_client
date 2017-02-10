<?php
require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemAttributeVariants
 * creates item variants from attribute combinations
 */
class RequestContainer_SetItemAttributeVariants extends RequestContainer
{
	/**
	 * @var int
	 */
	private $itemId;

	/**
	 * @return RequestContainer_SetItemAttributeVariants
	 */
	public function __construct($itemId)
	{
		parent::__construct(SoapCall_SetItemAttributeVariants::MAX_ITEMS_PER_PAGES);
		$this->itemId = $itemId;
	}

	/**
	 * returns the assembled request
	 * @return mixed
	 */
	public function getRequest()
	{
		// create request for given item id
		$request = new PlentySoapRequest_SetItemAttributeVariants();
		$request->ItemID = $this->itemId;

		// prepare attribute value sets
		$request->SetAttributeValueSets = new ArrayOfPlentysoapobject_attributevariantlist();
		$request->SetAttributeValueSets->item = array();

		// transform every entry to a separate set
		foreach ($this->items as $attributeValueId)
		{
			// init list
			$attributeVariantList = new PlentySoapObject_AttributeVariantList();
			$attributeVariantList->AttributeValueIDs = new ArrayOfPlentysoapobject_integer();
			$attributeVariantList->AttributeValueIDs->item = array();

			$plentySoapObject_Integer = new PlentySoapObject_Integer();
			$plentySoapObject_Integer->intValue = $attributeValueId;

			// store value
			$attributeVariantList->AttributeValueIDs->item[] = $plentySoapObject_Integer;

			// store list
			$request->SetAttributeValueSets->item[] = $attributeVariantList;
		}

		return $request;
	}
}