<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once 'Request_GetProperties.class.php';

/**
 * Class SoapCall_GetProperties
 */
class SoapCall_GetProperties extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_LINKED_ITEMS_PER_PAGE = 100;

	/**
	 * @var int
	 */
	private $page = 0;

	/**
	 * @var int
	 */
	private $pages = -1;

	/**
	 * @var int
	 */
	private $startAtPage = 0;

	/**
	 * @var PlentySoapRequest_GetProperties
	 */
	private $plentySoapRequest = null;

	/**
	 * @var array
	 */
	private $storeProperties;

	/**
	 * @var array
	 */
	private $storePropertyChoices;

	/**
	 * @var array
	 */
	private $storeAmazonLists;

	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->storeProperties = array();
	}

	/**
	 *
	 */
	public function execute()
	{
		//TODO remove after debugging
		//list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart(__CLASS__);

		if ($this->pages == -1)
		{
			try
			{
				$request = new Request_GetProperties(self::MAX_LINKED_ITEMS_PER_PAGE);
				//TODO remove after debugging
				//$this->plentySoapRequest = $request->getRequest($lastUpdate, $currentTime, $this->startAtPage);
				$this->plentySoapRequest = $request->getRequest(null, null, $this->startAtPage);

				if ($this->startAtPage > 0)
				{
					$this->debug(__FUNCTION__ . " Starting at page " . $this->startAtPage);
				}

				/*
				 * do soap call
				 */
				/** @var PlentySoapResponse_GetProperties $response */
				$response = $this->getPlentySoap()->GetProperties($this->plentySoapRequest);

				if (($response->Success == true) && isset($response->Properties))
				{
					// request successful, processing data..

					/** @noinspection PhpParamsInspection */
					$propertiesFound = count($response->Properties->item);
					$pagesFound = $response->Pages;

					$this->debug(__FUNCTION__ . ' Request Success - properties found : ' . $propertiesFound . ' / pages : ' . $pagesFound);

					// process response
					$this->responseInterpretation($response);

					if ($pagesFound > $this->page)
					{
						$this->page = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						//TODO remove after debugging
						//lastUpdatePageUpdate(__CLASS__, $this->page);
						$this->executePages();

					}
				} else
				{
					if (($response->Success == true) && !isset($response->Properties))
					{
						// request successful, but no data to process
						$this->debug(__FUNCTION__ . ' Request Success -  but no matching properties found');
					} else
					{
						$this->debug(__FUNCTION__ . ' Request Error');
					}
				}
			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		} else
		{
			$this->executePages();
		}

		$this->storeToDB();
		//TODO remove after debugging
		//lastUpdateFinish($currentTime, __CLASS__);
	}

	/**
	 * @param PlentySoapResponse_GetProperties $response
	 */
	private function responseInterpretation(PlentySoapResponse_GetProperties $response)
	{
		if (is_array($response->Properties->item))
		{

			foreach ($response->Properties->item AS $property)
			{
				$this->processProperty($property);
			}
		} else
		{
			$this->processProperty($response->Properties->item);
		}
	}

	/**
	 * @param PlentySoapObject_Property $property
	 */
	private function processProperty($property)
	{
		$propertyID = intval($property->PropertyID);

		$this->storeProperties[$propertyID] = array(
			/*'AmazonList'                  => $property->AmazonList, ignored here and processed separately */
			/*'PropertyChoice'              => $property->PropertyChoice ,ignored here and processed separately */

			'PropertyID'                  => $propertyID,
			'PropertyGroupID'             => $property->PropertyGroupID,
			'PropertyBackendName'         => $property->PropertyBackendName,
			'BeezUP'                      => $property->BeezUP,
			'Description'                 => $property->Description,
			'EbayLayout'                  => $property->EbayLayout,
			'EbayProperty'                => $property->EbayProperty,
			'Home24Property'              => $property->Home24Property,
			'Idealo'                      => $property->Idealo,
			'Kauflux'                     => $property->Kauflux,
			'Lang'                        => $property->Lang,
			'Markup'                      => $property->Markup,
			'NeckermannComponent'         => $property->NeckermannComponent,
			'NeckermannExternalComponent' => $property->NeckermannExternalComponent,
			'NeckermannLogoId'            => $property->NeckermannLogoId,
			'Notice'                      => $property->Notice,
			'OrderProperty'               => $property->OrderProperty,
			'Position'                    => $property->Position,
			'PropertyFrontendName'        => $property->PropertyFrontendName,
			'PropertyType'                => $property->PropertyType,
			'PropertyUnit'                => $property->PropertyUnit,
			'RicardoLayout'               => $property->RicardoLayout,
			'Searchable'                  => $property->Searchable,
			'ShopShare'                   => $property->ShopShare,
			'ShowInItemList'              => $property->ShowInItemList,
			'ShowInPDF'                   => $property->ShowInPDF,
			'ShowOnItemPage'              => $property->ShowOnItemPage,
			'Yatego'                      => $property->Yatego,
		);

		// process PropertyChoice
		if ($property->PropertyChoice)
		{
			if (is_array($property->PropertyChoice->item))
			{
				foreach ($property->PropertyChoice->item as $propertyChoice)
				{
					$this->processPropertyChoice($propertyID, $propertyChoice);
				}
			} else
			{
				$this->processPropertyChoice($propertyID, $property->PropertyChoice->item);
			}
		}

		// process AmazonList
		if ($property->AmazonList)
		{
			if (is_array($property->AmazonList->item))
			{
				foreach ($property->AmazonList->item as $amazonList)
				{
					$this->processAmazonList($propertyID, $amazonList);
				}
			} else
			{
				$this->processAmazonList($propertyID, $property->AmazonList->item);
			}
		}
	}

	/**
	 * @param int                             $propertyID
	 * @param PlentySoapObject_PropertyChoice $propertyChoice
	 */
	private function processPropertyChoice($propertyID, $propertyChoice)
	{
		$this->storePropertyChoices[] = array(
			'PropertyID'  => $propertyID,
			'Description' => $propertyChoice->Description,
			'Lang'        => $propertyChoice->Lang,
			'Name'        => $propertyChoice->Name,
			'SelectionID' => $propertyChoice->SelectionID,
		);
	}

	/**
	 * @param int                              $propertyID
	 * @param PlentySoapObject_PropertyAmazone $amazonList
	 */
	private function processAmazonList($propertyID, $amazonList)
	{
		$this->storeAmazonLists[] = array(
			'PropertyID'        => $propertyID,
			'AmazonCorrelation' => $amazonList->AmazonCorrelation,
			'AmazonGenre'       => $amazonList->AmazonGenre,
		);
	}

	private function executePages()
	{
		while ($this->pages > $this->page)
		{
			$this->plentySoapRequest->Page = $this->page;
			try
			{
				$response = $this->getPlentySoap()->GetProperties($this->plentySoapRequest);

				if ($response->Success == true)
				{
					/** @noinspection PhpParamsInspection */
					$propertiesFound = count($response->Properties->item);
					$this->debug(__FUNCTION__ . ' Request Success - properties found : ' . $propertiesFound . ' / page : ' . $this->page);

					// auswerten
					$this->responseInterpretation($response);
				}

				$this->page++;
				//TODO remove after debugging
				//lastUpdatePageUpdate(__CLASS__, $this->page);

			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
	}

	private function storeToDB()
	{
		$countProperties = count($this->storeProperties);
		$countPropertyChoice = count($this->storePropertyChoices);
		$countAmazonList = count($this->storeAmazonLists);

		if ($countProperties > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countProperties property records ...");
			DBQuery::getInstance()->truncate('TRUNCATE `Properties`');
			DBQuery::getInstance()->insert('INSERT INTO `Properties`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storeProperties));
		}

		if ($countPropertyChoice > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countPropertyChoice property choice records ...");
			DBQuery::getInstance()->truncate('TRUNCATE `PropertyChoices`');
			DBQuery::getInstance()->insert('INSERT INTO `PropertyChoices`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storePropertyChoices));
		}

		if ($countAmazonList > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countAmazonList amazon list records ...");
			DBQuery::getInstance()->truncate('TRUNCATE `PropertyAmazonList`');
			DBQuery::getInstance()->insert('INSERT INTO `PropertyAmazonList`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storeAmazonLists));
		}
	}
}