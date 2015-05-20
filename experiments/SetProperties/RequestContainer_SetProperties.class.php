<?php
require_once ROOT . 'includes/RequestContainer.class.php';


class RequestContainer_SetProperties extends RequestContainer
{

	public function __construct($capacity)
	{
		parent::__construct($capacity);
	}

	/**
	 * returns the assembled request
	 *
	 * @return mixed
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_SetProperties();
		$request->Properties = new ArrayOfPlentysoapobject_setproperty();
		$request->Properties->item = array();

		foreach ($this->items as &$propertyData)
		{
			$property = new PlentySoapObject_SetProperty();

			fillObjectFromArray($property, $propertyData, array(
				'PropertyChoice' => null,
				'AmazonList'     => null,
			));

			if (isset($propertyData['PropertyChoice']) && is_array($propertyData['PropertyChoice']))
			{
				$property->PropertyChoice = new ArrayOfPlentysoapobject_setpropertychoice();
				$property->PropertyChoice->item = array();

				foreach ($propertyData['PropertyChoice'] as &$propertyChoiceData)
				{
					$propertyChoice = new PlentySoapObject_SetPropertyChoice();

					fillObjectFromArray($propertyChoice, $propertyChoiceData);

					$property->PropertyChoice->item[] = $propertyChoice;
				}
			}

			if (isset($propertyData['AmazonList']) && is_array($propertyData['AmazonList']))
			{
				$property->AmazonList = new ArrayOfPlentysoapobject_setpropertyamazon();
				$property->AmazonList->item = array();

				foreach ($propertyData['AmazonList'] as &$amazonListData)
				{
					$amazonList = new PlentySoapObject_SetPropertyAmazon();

					fillObjectFromArray($amazonList, $amazonListData);

					$property->AmazonList->item[] = $amazonList;
				}
			}

			$request->Properties->item[] = $property;
		}

		return $request;
	}
}
