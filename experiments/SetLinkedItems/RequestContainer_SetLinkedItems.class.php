<?php

require_once ROOT . 'includes/RequestContainer.class.php';
require_once ROOT . 'includes/SKUHelper.php';

/**
 * Class RequestContainer_SetLinkedItems
 */
class RequestContainer_SetLinkedItems extends RequestContainer
{
	/**
	 * @var int
	 */
	private $mainItemId;

	/**
	 * @var string
	 */
	private $relationship;

	/**
	 * @var bool
	 */
	private $deleteLink;

	/**
	 * @param string $mainItemId
	 * @param string $relationship
	 * @param bool   $deleteLink
	 */
	public function __construct($mainItemId, $relationship, $deleteLink)
	{
		parent::__construct(SoapCall_SetLinkedItems::MAX_LINKED_ITEMS_PER_PAGE);

		$this->mainItemId = $mainItemId;
		$this->relationship = $relationship;
		$this->deleteLink = $deleteLink;
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetLinkedItems
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_SetLinkedItems();

		$request->MainItemSKU = Values2SKU($this->mainItemId);
		$request->CrosssellingList = new ArrayOfPlentysoapobject_setlinkeditems();

		$request->CrosssellingList->item = array();
		foreach ($this->items as &$linkedItemId)
		{
			$linkedItem = new PlentySoapObject_SetLinkedItems();

			$linkedItem->CrossItemSKU = Values2SKU($linkedItemId);
			$linkedItem->Relationship = $this->relationship;
			$linkedItem->deleteLink = $this->deleteLink;

			$request->CrosssellingList->item[] = $linkedItem;
		}

		return $request;
	}
}
