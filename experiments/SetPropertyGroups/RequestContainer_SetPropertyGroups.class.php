<?php
require_once ROOT . 'includes/RequestContainer.class.php';

class RequestContainer_SetPropertyGroups extends RequestContainer
{

	public function __construct($capacity)
	{
		parent::__construct($capacity);
	}

	public function getRequest()
	{
		$request = new PlentySoapRequest_SetPropertyGroups();
		$request->PropertyGroups = new ArrayOfPlentysoapobject_setpropertygroup();
		$request->PropertyGroups->item = array();

		foreach ($this->items as &$propertyGroupData)
		{
			$setPropertyGroup = new PlentySoapObject_SetPropertyGroup();

			fillObjectFromArray($setPropertyGroup, $propertyGroupData);

			$request->PropertyGroups->item[] = $setPropertyGroup;
		}

		return $request;
	}
}
