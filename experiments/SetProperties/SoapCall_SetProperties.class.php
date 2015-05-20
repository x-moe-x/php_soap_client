<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetProperties.class.php';
require_once ROOT . 'includes/FillObjectFromArray.php';

class SoapCall_SetProperties extends PlentySoapCall
{

	/**
	 * @var int
	 */
	const MAX_PROPERTIES_PER_PAGE = 50;

	/**
	 * @var array
	 */
	private $restrictedProperties = array(
		1,
		2
	);

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__ . ' writing property data ...');
		try
		{
			// 1. get all property group records
			$unwrittenPropertyData = DBQuery::getInstance()->select($this->getQuery());

			$this->getLogger()->debug(__FUNCTION__ . ' found ' . $unwrittenPropertyData->getNumRows() . ' records...');

			// 2. for every 50 updates ...
			$countWrittenUpdates = 0;
			for ($page = 0, $maxPage = ceil($unwrittenPropertyData->getNumRows() / self::MAX_PROPERTIES_PER_PAGE); $page < $maxPage; $page++)
			{

				// ... prepare a separate request ...
				$request = new RequestContainer_SetProperties(self::MAX_PROPERTIES_PER_PAGE);

				// ... fill in data
				$writtenUpdates = array();
				while (!$request->isFull() && ($unwrittenUpdate = $unwrittenPropertyData->fetchAssoc()))
				{
					$id = intval($unwrittenUpdate['id']);
					$request->add(array(
						'PropertyID'                  => $unwrittenUpdate['PropertyID'],
						'PropertyGroupID'             => $unwrittenUpdate['PropertyGroupID'],
						'PropertyBackendName'         => $unwrittenUpdate['PropertyBackendName'],
						'PropertyType'                => $unwrittenUpdate['PropertyType'],
						'Position'                    => $unwrittenUpdate['Position'],
						'Lang'                        => $unwrittenUpdate['Lang'],
						'PropertyFrontendName'        => $unwrittenUpdate['PropertyFrontendName'],
						'Description'                 => $unwrittenUpdate['Description'],
						'Searchable'                  => $unwrittenUpdate['Searchable'],
						'ShowInItemList'              => $unwrittenUpdate['ShowInItemList'],
						'ShowInPDF'                   => $unwrittenUpdate['ShowInPDF'],
						'ShowOnItemPage'              => $unwrittenUpdate['ShowOnItemPage'],
						'PropertyUnit'                => $unwrittenUpdate['PropertyUnit'],
						'OrderProperty'               => $unwrittenUpdate['OrderProperty'],
						'Markup'                      => $unwrittenUpdate['Markup'],
						'Notice'                      => $unwrittenUpdate['Notice'],
						'BeezUP'                      => $unwrittenUpdate['BeezUP'],
						'EbayLayout'                  => $unwrittenUpdate['EbayLayout'],
						'EbayProperty'                => $unwrittenUpdate['EbayProperty'],
						'Home24Property'              => $unwrittenUpdate['Home24Property'],
						'Idealo'                      => $unwrittenUpdate['Idealo'],
						'Kauflux'                     => $unwrittenUpdate['Kauflux'],
						'NeckermannComponent'         => $unwrittenUpdate['NeckermannComponent'],
						'NeckermannExternalComponent' => $unwrittenUpdate['NeckermannExternalComponent'],
						'NeckermannLogoId'            => $unwrittenUpdate['NeckermannLogoId'],
						'RicardoLayout'               => $unwrittenUpdate['RicardoLayout'],
						'ShopShare'                   => $unwrittenUpdate['ShopShare'],
						'Yatego'                      => $unwrittenUpdate['Yatego'],
					), $id);

					$writtenUpdates[] = $id;
				}

				$propertyChoicesDBResult = DBQuery::getInstance()->select('SELECT `SetPropertiesID`, `SelectionID`, `Name`, `Lang`, `Description` FROM SetPropertyChoices WHERE SetPropertiesID IN (' . implode(',', $writtenUpdates) . ')');
				$amazonListChoicesDBResult = DBQuery::getInstance()->select('SELECT `SetPropertiesID`, `AmazonGenre`, `AmazonCorrelation` FROM SetPropertyAmazonList WHERE SetPropertiesID IN (' . implode(',', $writtenUpdates) . ')');

				while ($row = $propertyChoicesDBResult->fetchAssoc())
				{
					$request->addPropertyChoice(array(
						'SelectionID' => $row['SelectionID'],
						'Name'        => $row['Name'],
						'Lang'        => $row['Lang'],
						'Description' => $row['Description'],
					), $row['SetPropertiesID']);
				}

				while ($row = $amazonListChoicesDBResult->fetchAssoc())
				{
					$request->addAmazonList(array(
						'AmazonGenre'       => $row['AmazonGenre'],
						'AmazonCorrelation' => $row['AmazonCorrelation'],
					), $row['SetPropertiesID']);
				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->SetProperties($request->getRequest());

				// 4. if successful ...
				if ($response->Success == true)
				{
					// ... then delete specified elements from setCurrentStocks
					DBQuery::getInstance()->delete('DELETE FROM SetProperties WHERE id IN (' . implode(',', $writtenUpdates) . ')');
					$countWrittenUpdates += count($writtenUpdates);
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}
			if ($countWrittenUpdates > 0)
			{
				// ... and update
				$this->debug(__FUNCTION__ . ' ... done. Please perform a GetProperties call afterwards');
			} else
			{
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
  PropertyID,
  PropertyGroupID,
  PropertyBackendName,
  PropertyType,
  Position,
  Lang,
  PropertyFrontendName,
  Description,
  Searchable,
  ShowInItemList,
  ShowInPDF,
  ShowOnItemPage,
  PropertyUnit,
  OrderProperty,
  Markup,
  Notice,
  BeezUP,
  EbayLayout,
  EbayProperty,
  Home24Property,
  Idealo,
  Kauflux,
  NeckermannComponent,
  NeckermannExternalComponent,
  NeckermannLogoId,
  RicardoLayout,
  ShopShare,
  Yatego
FROM SetProperties
WHERE PropertyID IS NULL OR PropertyID NOT IN (' . implode(',', $this->restrictedProperties) . ')';
	}
}
