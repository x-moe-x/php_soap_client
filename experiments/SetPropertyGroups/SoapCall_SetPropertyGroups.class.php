<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetPropertyGroups.class.php';
require_once ROOT . 'includes/FillObjectFromArray.php';

class SoapCall_SetPropertyGroups extends PlentySoapCall
{

	/**
	 * @var int
	 */
	const MAX_PROPERTY_GROUP_RECORDS_PER_PAGE = 25;

	/**
	 * @var array
	 */
	private $restrictedPropertyGroups = array(1);

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__ . ' writing property group data ...');
		try
		{
			// 1. get all property group records
			$unwrittenPropertyGroupData = DBQuery::getInstance()->select($this->getQuery());

			$this->getLogger()->debug(__FUNCTION__ . ' found ' . $unwrittenPropertyGroupData->getNumRows() . ' records...');

			// 2. for every 25 updates ...
			for ($page = 0, $maxPage = ceil($unwrittenPropertyGroupData->getNumRows() / self::MAX_PROPERTY_GROUP_RECORDS_PER_PAGE); $page < $maxPage; $page++)
			{

				// ... prepare a separate request ...
				$request = new RequestContainer_SetPropertyGroups(self::MAX_PROPERTY_GROUP_RECORDS_PER_PAGE);

				// ... fill in data
				$writtenUpdates = array();
				while (!$request->isFull() && ($unwrittenUpdate = $unwrittenPropertyGroupData->fetchAssoc()))
				{
					$propertyGroupID = intval($unwrittenUpdate['PropertyGroupID']);
					$request->add(array(
						'PropertyGroupID'   => $propertyGroupID,
						'BackendName'       => $unwrittenUpdate['BackendName'],
						'Lang'              => $unwrittenUpdate['Lang'],
						'PropertyGroupTyp'  => $unwrittenUpdate['PropertyGroupTyp'],
						'IsMarkupPercental' => $unwrittenUpdate['IsMarkupPercental'],
						'FrontendName'      => $unwrittenUpdate['FrontendName'],
						'Description'       => $unwrittenUpdate['Description'],
					));

				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->SetPropertyGroups($request->getRequest());

				// 4. if successful ...
				if ($response->Success == true)
				{
					// ... then delete specified elements from setCurrentStocks
					//TODO implement after debugging
					$this->debug(__FUNCTION__ . ' implement: delete written updates from db');

					// ... and update
					//TODO implement after debugging
					$this->debug(__FUNCTION__ . ' implement: update written updates in db');

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

	private function getQuery()
	{
		return 'SELECT
  PropertyGroupID,
  BackendName,
  Lang,
  PropertyGroupTyp,
  IsMarkupPercental,
  FrontendName,
  Description
FROM SetPropertyGroups WHERE PropertyGroupID NOT IN (' . implode(',', $this->restrictedPropertyGroups) . ')';
	}
}
