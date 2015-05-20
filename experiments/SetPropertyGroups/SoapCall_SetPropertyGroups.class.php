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
			$countWrittenUpdates = 0;
			for ($page = 0, $maxPage = ceil($unwrittenPropertyGroupData->getNumRows() / self::MAX_PROPERTY_GROUP_RECORDS_PER_PAGE); $page < $maxPage; $page++)
			{

				// ... prepare a separate request ...
				$request = new RequestContainer_SetPropertyGroups(self::MAX_PROPERTY_GROUP_RECORDS_PER_PAGE);

				// ... fill in data
				$writtenUpdates = array();
				while (!$request->isFull() && ($unwrittenUpdate = $unwrittenPropertyGroupData->fetchAssoc()))
				{
					$request->add(array(
						'PropertyGroupID'   => $unwrittenUpdate['PropertyGroupID'],
						'BackendName'       => $unwrittenUpdate['BackendName'],
						'Lang'              => $unwrittenUpdate['Lang'],
						'PropertyGroupTyp'  => $unwrittenUpdate['PropertyGroupTyp'],
						'IsMarkupPercental' => $unwrittenUpdate['IsMarkupPercental'],
						'FrontendName'      => $unwrittenUpdate['FrontendName'],
						'Description'       => $unwrittenUpdate['Description'],
					));

					$writtenUpdates[$unwrittenUpdate['id']] = $unwrittenUpdate;
				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->SetPropertyGroups($request->getRequest());

				// 4. if successful ...
				if ($response->Success == true)
				{
					// ... then delete specified elements from setCurrentStocks
					DBQuery::getInstance()->delete('DELETE FROM SetPropertyGroups WHERE id IN (' . implode(',', array_keys($writtenUpdates)) . ')');
					$countWrittenUpdates += count($writtenUpdates);
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}
			if ($countWrittenUpdates > 0){
				// ... and update
				$this->debug(__FUNCTION__ . ' ... done. Please perform a GetPropertyGroups call afterwards');
			} else {
				$this->debug(__FUNCTION__ . ' ... done.');
			}

		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	private function getQuery()
	{
		return 'SELECT
  id,
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
