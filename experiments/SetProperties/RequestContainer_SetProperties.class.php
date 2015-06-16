<?php
require_once ROOT . 'includes/RequestContainer.class.php';


/**
 * Class RequestContainer_SetProperties
 */
class RequestContainer_SetProperties extends RequestContainer
{

	/**
	 * creates a new RequestContainer with a specified capacity
	 *
	 * @param int $capacity
	 *
	 * @return RequestContainer_SetProperties
	 */
	public function __construct($capacity)
	{
		parent::__construct($capacity);
	}

	/**
	 * if container isn't full an item is added at it's end
	 *
	 * @param array $item
	 *
	 * @param int   $id
	 */
	public function add($item, $id)
	{
		if (is_array($item))
		{
			parent::add(array_merge($item, array(
				'PropertyChoice' => null,
				'AmazonList'     => null
			)), $id);
		}
	}

	/**
	 * if record identified by $id exists in container, add $propertyChoice
	 *
	 * @param array $propertyChoice
	 * @param int   $id
	 */
	public function addPropertyChoice(array $propertyChoice, $id)
	{
		if (array_key_exists($id, $this->items))
		{
			if ($this->items[$id]['PropertyType'] === 'selection')
			{
				$this->addSubArray($propertyChoice, $id, 'PropertyChoice');
			} else
			{
				throw new RuntimeException('Trying to add PropertyChoice to non-selection record with id: ' . $id);
			}
		} else
		{
			throw new RuntimeException('Trying to add PropertyChoice to non-existing record with id: ' . $id);
		}
	}

	/**
	 * if record identified by $id exists in container, add sub-array $item to given $key
	 *
	 * @param array  $item sub-array
	 * @param int    $id
	 * @param string $key
	 */
	private function addSubArray(array $item, $id, $key)
	{
		if (is_null($this->items[$id][$key]))
		{
			$this->items[$id][$key] = array();
		}
		$this->items[$id][$key][] = $item;

	}

	/**
	 * if record identified by $id exists in container, add $amazonList
	 *
	 * @param array $amazonList
	 * @param int   $id
	 */
	public function addAmazonList(array $amazonList, $id)
	{
		if (array_key_exists($id, $this->items))
		{
			$this->addSubArray($amazonList, $id, 'AmazonList');
		} else
		{
			throw new RuntimeException('Trying to add AmazonList to non-existing record with id: ' . $id);
		}
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetProperties
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_SetProperties();
		$request->Properties = new ArrayOfPlentysoapobject_setproperty();
		$request->Properties->item = array();

		foreach ($this->items as &$propertyData)
		{
			$property = new PlentySoapObject_SetProperty();

			// fill data to object, override PropertyChoice and AmazonList keys to be processed afterwards
			fillObjectFromArray($property, $propertyData, array(
				'PropertyChoice' => null,
				'AmazonList'     => null,
			));

			// process PropertyChoice ...
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

			// process AmazonList ...
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
