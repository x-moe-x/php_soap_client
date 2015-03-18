<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsBase.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class SoapCall_GetItemsBase
 */
class SoapCall_GetItemsBase extends PlentySoapCall
{
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
	 * @var PlentySoapRequest_GetItemsBase
	 */
	private $plentySoapRequest = null;

	/**
	 * @var array
	 */
	private $processedItemsBases;

	/**
	 * @var array
	 */
	private $processedAttributeValueSets;

	/**
	 * @return SoapCall_GetItemsBase
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
		$this->processedItemsBases = array();
		$this->processedAttributeValueSets = array();
		$this->avsMarkedForDeletion = array();
	}

	/**
	 * @return void
	 */
	public function execute()
	{

		list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart(__CLASS__);

		if ($this->pages == -1)
		{
			try
			{
				$this->plentySoapRequest = Request_GetItemsBase::getRequest($lastUpdate, $currentTime, $this->startAtPage);

				if ($this->startAtPage > 0)
				{
					$this->debug(__FUNCTION__ . " Starting at page " . $this->startAtPage);
				}

				/*
				 * do soap call
				 */
				$response = $this->getPlentySoap()->GetItemsBase($this->plentySoapRequest);

				if (($response->Success == true) && isset($response->ItemsBase))
				{
					// request successful, processing data..

					/** @noinspection PhpParamsInspection */
					$articlesFound = count($response->ItemsBase->item);
					$pagesFound = $response->Pages;

					$this->debug(__FUNCTION__ . ' Request Success - articles found : ' . $articlesFound . ' / pages : ' . $pagesFound);

					// process response
					$this->responseInterpretation($response);

					if ($pagesFound > $this->page)
					{
						$this->page = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						lastUpdatePageUpdate(__CLASS__, $this->page);
						$this->executePages();

					}
				} else
				{
					if (($response->Success == true) && !isset($response->ItemsBase))
					{
						// request successful, but no data to process
						$this->debug(__FUNCTION__ . ' Request Success -  but no matching articles found');
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
		lastUpdateFinish($currentTime, __CLASS__);
	}

	/**
	 * @param PlentySoapResponse_GetItemsBase $response
	 *
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsBase $response)
	{
		if (is_array($response->ItemsBase->item))
		{

			foreach ($response->ItemsBase->item AS $itemsBase)
			{
				$this->processItemsBase($itemsBase);
			}
		} else
		{
			$this->processItemsBase($response->ItemsBase->item);
		}
	}

	/**
	 * @param PlentySoapObject_ItemBase $itemsBase
	 *
	 * @return void
	 */
	private function processItemsBase($itemsBase)
	{
		// prepare ItemsBase for persistent storage

		$itemID = intval($itemsBase->ItemID);

		$this->processedItemsBases[$itemID] = array(
			/*	'ASIN'						=> $oItemsBase->ASIN, moved to AttributeValueSets in 109 api definition	*/
			/*	'AttributeValueSets'		=> $oItemsBase->AttributeValueSets,	skipped here and stored to separate table	*/
			/*	'Availability'				=> $oItemsBase->Availability,	currently considered irrelevant	*/
			'BundleType'          => $itemsBase->BundleType,
			/*	'Categories'				=> $oItemsBase->Categories,	ignored since not part of the request	*/
			'Condition'           => $itemsBase->Condition,
			'CustomsTariffNumber' => $itemsBase->CustomsTariffNumber,
			'DeepLink'            => $itemsBase->DeepLink,
			'EAN1'                => $itemsBase->EAN1,
			'EAN2'                => $itemsBase->EAN2,
			'EAN3'                => $itemsBase->EAN3,
			'EAN4'                => $itemsBase->EAN4,
			/*	'EbayEPID'					=> $oItemsBase->EbayEPID, ignored since removed in 109 api definition	*/
			'ExternalItemID'      => $itemsBase->ExternalItemID,
			'FSK'                 => $itemsBase->FSK,
			/*	'FreeTextFields'			=> $oItemsBase->FreeTextFields,	replaced with it's subitems	*/
			'Free1'               => $itemsBase->FreeTextFields->Free1,
			'Free2'               => $itemsBase->FreeTextFields->Free2,
			'Free3'               => $itemsBase->FreeTextFields->Free3,
			'Free4'               => $itemsBase->FreeTextFields->Free4,
			'Free5'               => $itemsBase->FreeTextFields->Free5,
			'Free6'               => $itemsBase->FreeTextFields->Free6,
			'Free7'               => $itemsBase->FreeTextFields->Free7,
			'Free8'               => $itemsBase->FreeTextFields->Free8,
			'Free9'               => $itemsBase->FreeTextFields->Free9,
			'Free10'              => $itemsBase->FreeTextFields->Free10,
			'Free11'              => $itemsBase->FreeTextFields->Free11,
			'Free12'              => $itemsBase->FreeTextFields->Free12,
			'Free13'              => $itemsBase->FreeTextFields->Free13,
			'Free14'              => $itemsBase->FreeTextFields->Free14,
			'Free15'              => $itemsBase->FreeTextFields->Free15,
			'Free16'              => $itemsBase->FreeTextFields->Free16,
			'Free17'              => $itemsBase->FreeTextFields->Free17,
			'Free18'              => $itemsBase->FreeTextFields->Free18,
			'Free19'              => $itemsBase->FreeTextFields->Free19,
			'Free20'              => $itemsBase->FreeTextFields->Free20,
			'HasAttributes'       => $itemsBase->HasAttributes,
			'ISBN'                => $itemsBase->ISBN,
			'Inactive'            => $itemsBase->Availability->Inactive,
			/* just inactive field needed */
			'Inserted'            => $itemsBase->Inserted,
			/*	'ItemAttributeMarkup'		=> $oItemsBase->ItemAttributeMarkup,	ignored since not part of the request	*/
			'ItemID'              => $itemID,
			'ItemNo'              => $itemsBase->ItemNo,
			/*	'ItemProperties'			=> $oItemsBase->ItemProperties,	ignored since not part of the request	*/
			/*	'ItemSuppliers'				=> $oItemsBase->ItemSuppliers,	skipped since handled by another request	*/
			/*	'ItemURL'					=> $oItemsBase->ItemURL,	ignored since not part of the request	*/
			'LastUpdate'          => $itemsBase->LastUpdate,
			'Marking1ID'          => $itemsBase->Marking1ID,
			'Marking2ID'          => $itemsBase->Marking2ID,
			'Model'               => $itemsBase->Model,
			/*	'Others'					=> $oItemsBase->Others,	ignored since not part of the request	*/
			/*	'ParcelServicePresetIDs'	=> $oItemsBase->ParcelServicePresetIDs,*/
			/*	'PriceSet'					=> $oItemsBase->PriceSet,	currently considered irrelevant	*/
			'ProducerID'          => $itemsBase->ProducerID,
			'ProducingCountryID'  => $itemsBase->ProducingCountryID,
			'Published'           => $itemsBase->Published,
			/*	'Stock'						=> $oItemsBase->Stock,	currently considered irrelevant, except MainWarehouseID	*/
			'MainWarehouseID'     => $itemsBase->Stock->MainWarehouseID,
			'StorageLocation'     => $itemsBase->StorageLocation,
			/*	'Texts'						=> $oItemsBase->Texts,	replaced with it's subitems	*/
			'Keywords'            => DBQuery::getInstance()->escapeString($itemsBase->Texts->Keywords),
			'Lang'                => DBQuery::getInstance()->escapeString($itemsBase->Texts->Lang),
			'LongDescription'     => DBQuery::getInstance()->escapeString($itemsBase->Texts->LongDescription),
			'MetaDescription'     => DBQuery::getInstance()->escapeString($itemsBase->Texts->MetaDescription),
			'Name'                => DBQuery::getInstance()->escapeString($itemsBase->Texts->Name),
			'Name2'               => DBQuery::getInstance()->escapeString($itemsBase->Texts->Name2),
			'Name3'               => DBQuery::getInstance()->escapeString($itemsBase->Texts->Name3),
			'ShortDescription'    => DBQuery::getInstance()->escapeString($itemsBase->Texts->ShortDescription),
			'TechnicalData'       => DBQuery::getInstance()->escapeString($itemsBase->Texts->TechnicalData),
			/*
			 *	end of Texts' replacement
			 */
			'Type'                => $itemsBase->Type,
			'VATInternalID'       => $itemsBase->VATInternalID,
			'WebShopSpecial'      => $itemsBase->WebShopSpecial
		);

		// process AttributeValueSets
		if ($itemsBase->HasAttributes)
		{
			if (is_array($itemsBase->AttributeValueSets->item))
			{
				foreach ($itemsBase->AttributeValueSets->item as $attributeValueSet)
				{
					$this->processAttributeValueSet($itemsBase->ItemID, $attributeValueSet);
				}
			} else
			{
				$this->processAttributeValueSet($itemsBase->ItemID, $itemsBase->AttributeValueSets->item);
			}
		}
	}

	/**
	 * @param int                                    $itemID
	 * @param PlentySoapObject_ItemAttributeValueSet $attributeValueSet
	 *
	 * @return void
	 */
	private function processAttributeValueSet($itemID, $attributeValueSet)
	{
		// prepare AttributeValueSet for persistent storage
		$this->processedAttributeValueSets[] = array(
			'ItemID'                => $itemID,
			'AttributeValueSetID'   => $attributeValueSet->AttributeValueSetID,
			'AttributeValueSetName' => $attributeValueSet->AttributeValueSetName,
			'Availability'          => $attributeValueSet->Availability,
			'EAN'                   => $attributeValueSet->EAN,
			'EAN2'                  => $attributeValueSet->EAN2,
			'EAN3'                  => $attributeValueSet->EAN3,
			'EAN4'                  => $attributeValueSet->EAN4,
			'ASIN'                  => $attributeValueSet->ASIN,
			'ColliNo'               => $attributeValueSet->ColliNo,
			'PriceID'               => $attributeValueSet->PriceID,
			'PurchasePrice'         => $attributeValueSet->PurchasePrice
		);
	}

	/**
	 * @return void
	 */
	private function executePages()
	{
		while ($this->pages > $this->page)
		{
			$this->plentySoapRequest->Page = $this->page;
			try
			{
				$response = $this->getPlentySoap()->GetItemsBase($this->plentySoapRequest);

				if ($response->Success == true)
				{
					/** @noinspection PhpParamsInspection */
					$articlesFound = count($response->ItemsBase->item);
					$this->debug(__FUNCTION__ . ' Request Success - articles found : ' . $articlesFound . ' / page : ' . $this->page);

					// auswerten
					$this->responseInterpretation($response);
				}

				$this->page++;
				lastUpdatePageUpdate(__CLASS__, $this->page);

			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
	}

	private function storeToDB()
	{
		// insert itemsbase
		$countItemsBases = count($this->processedItemsBases);
		$countAttributeValueSets = count($this->processedAttributeValueSets);

		if ($countItemsBases > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countItemsBases items base records ...");

			DBQuery::getInstance()->insert('INSERT INTO `ItemsBase`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedItemsBases));
		}

		if ($countAttributeValueSets > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countAttributeValueSets attribute value set records ...");

			DBQuery::getInstance()->delete('DELETE FROM `AttributeValueSets` WHERE `ItemID` IN (\'' . implode('\',\'', array_keys($this->processedItemsBases)) . '\')');
			DBQuery::getInstance()->insert('INSERT INTO `AttributeValueSets`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedAttributeValueSets));
		}
	}

}
