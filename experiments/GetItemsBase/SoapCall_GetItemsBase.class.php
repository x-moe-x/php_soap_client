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
	 * @var array
	 */
	private $processedCategories;

	/**
	 * @var array
	 */
	private $processedProperties;

	/**
	 * @var array
	 */
	private $processedAvailabilityRecords;

	/**
	 * @return SoapCall_GetItemsBase
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
		$this->processedItemsBases = array();
		$this->processedAttributeValueSets = array();
		$this->avsMarkedForDeletion = array();
		$this->processedProperties = array();
		$this->processedAvailabilityRecords = array();
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
			/*	'Availability'				=> $oItemsBase->Availability, skipped here and stored to separate table	*/
			'BundleType'          => $itemsBase->BundleType,
			/*	'Categories'				=> $oItemsBase->Categories,	skipped here and stored to separate table	*/
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
			/*	'ItemProperties'			=> $oItemsBase->ItemProperties,	skipped here and stored to separate table	*/
			/*	'ItemSuppliers'				=> $oItemsBase->ItemSuppliers,	skipped since handled by another request	*/
			'ItemURL'             => $itemsBase->ItemURL,
			'LastUpdate'          => $itemsBase->LastUpdate,
			'Marking1ID'          => $itemsBase->Marking1ID,
			'Marking2ID'          => $itemsBase->Marking2ID,
			'Model'               => $itemsBase->Model,
			/*	'Others'					=> $oItemsBase->Others,	ignored except position	*/
            'Others_Position'     => $itemsBase->Others->Position,
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
					$this->processAttributeValueSet($itemID, $attributeValueSet);
				}
			} else
			{
				$this->processAttributeValueSet($itemID, $itemsBase->AttributeValueSets->item);
			}
		}

		// process Categories
		if (is_array($itemsBase->Categories->item))
		{
			/** @var PlentySoapObject_ItemCategory $category */
			foreach ($itemsBase->Categories->item as $category)
			{
				$this->processCategories($itemID, $category);
			}
		} else
		{
			$this->processCategories($itemID, $itemsBase->Categories->item);
		}

		// process Properties
		if (is_array($itemsBase->ItemProperties->item))
		{
			/** @var PlentySoapObject_ItemProperty $property */
			foreach ($itemsBase->ItemProperties->item as $property)
			{
				$this->processProperties($itemID, $property);
			}
		} else
		{
			$this->processProperties($itemID, $itemsBase->ItemProperties->item);
		}

		// process Availability
		$this->processAvailability($itemID, $itemsBase->Availability);
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
	 * @param int                           $ItemID
	 * @param PlentySoapObject_ItemCategory $category
	 */
	private function processCategories($ItemID, $category)
	{
		// prepare Categories for persistent storage
		$this->processedCategories[] = array(
			'ItemID'                 => $ItemID,
			'ItemCategoryID'         => $category->ItemCategoryID,
			'ItemCategoryLevel'      => $category->ItemCategoryLevel,
			'ItemCategoryPath'       => $category->ItemCategoryPath,
			'ItemCategoryPathNames'  => $category->ItemCategoryPathNames,
			'ItemStandardCategory'   => 0,
			'RemoveCategoryFromItem' => $category->RemoveCategoryFromItem,
		);
	}

	/**
	 * @param int                           $ItemID
	 * @param PlentySoapObject_ItemProperty $property
	 */
	private function processProperties($ItemID, $property)
	{
		$this->processedProperties[] = array(
			'ItemID'                     => $ItemID,
			'PropertyID'                 => $property->PropertyID,
			'PropertyGroupID'            => $property->PropertyGroupID,
			'PropertyGroupBackendName'   => $property->PropertyGroupBackendName,
			'PropertyGroupFrontendName'  => $property->PropertyGroupFrontendName,
			'PropertyName'               => $property->PropertyName,
			'PropertySelectionID'        => $property->PropertySelectionID,
			'PropertySelectionName'      => $property->PropertySelectionName,
			'PropertyValue'              => $property->PropertyValue,
			'PropertyValueLang'          => $property->PropertyValueLang,
			'ShowInItemListingInWebshop' => $property->ShowInItemListingInWebshop,
			'ShowInOrderProcess'         => $property->ShowInOrderProcess,
			'ShowInPdfDocuments'         => $property->ShowInPdfDocuments,
			'ShowOnItemPageInWebshop'    => $property->ShowOnItemPageInWebshop,
		);
	}

	/**
	 * @param int                               $itemID
	 * @param PlentySoapObject_ItemAvailability $availability
	 */
	private function processAvailability($itemID, $availability)
	{
		$this->processedAvailabilityRecords[] = array(
			'ItemID'                     => $itemID,
			'AmazonFBA'                  => $availability->AmazonFBA,
			'AmazonFEDAS'                => $availability->AmazonFEDAS,
			'AmazonMultichannel'         => $availability->AmazonMultichannel,
			'AmazonMultichannelCom'      => $availability->AmazonMultichannelCom,
			'AmazonMultichannelDe'       => $availability->AmazonMultichannelDe,
			'AmazonMultichannelEs'       => $availability->AmazonMultichannelEs,
			'AmazonMultichannelFr'       => $availability->AmazonMultichannelFr,
			'AmazonMultichannelIt'       => $availability->AmazonMultichannelIt,
			'AmazonMultichannelUk'       => $availability->AmazonMultichannelUk,
			'AmazonProduct'              => $availability->AmazonProduct,
			'AvailabilityID'             => $availability->AvailabilityID,
			'AvailableUntil'             => $availability->AvailableUntil,
			'CouchCommerce'              => $availability->CouchCommerce,
			'GartenXXL'                  => $availability->GartenXXL,
			'Gimahhot'                   => $availability->Gimahhot,
			'GoogleBase'                 => $availability->GoogleBase,
			'Grosshandel'                => $availability->Grosshandel,
			'Hitmeister'                 => $availability->Hitmeister,
			'Hood'                       => $availability->Hood,
			'Inactive'                   => $availability->Inactive,
			'IntervalSalesOrderQuantity' => $availability->IntervalSalesOrderQuantity,
			'Laary'                      => $availability->Laary,
			'MaximumSalesOrderQuantity'  => $availability->MaximumSalesOrderQuantity,
			'MeinPaket'                  => $availability->MeinPaket,
			'Mercateo'                   => $availability->Mercateo,
			'MinimumSalesOrderQuantity'  => $availability->MinimumSalesOrderQuantity,
			'Moebelprofi'                => $availability->Moebelprofi,
			'Neckermann'                 => $availability->Neckermann,
			'Otto'                       => $availability->Otto,
			'PlusDe'                     => $availability->PlusDe,
			'Quelle'                     => $availability->Quelle,
			'Restposten'                 => $availability->Restposten,
			'ShopShare'                  => $availability->ShopShare,
			'Shopgate'                   => $availability->Shopgate,
			'Shopperella'                => $availability->Shopperella,
			'SumoScout'                  => $availability->SumoScout,
			'Tradoria'                   => $availability->Tradoria,
			'TradoriaCategory'           => $availability->TradoriaCategory,
			'Twenga'                     => $availability->Twenga,
			'WebAPI'                     => $availability->WebAPI,
			'Webshop'                    => $availability->Webshop,
			'Yatego'                     => $availability->Yatego,
			'Zalando'                    => $availability->Zalando,
			'Zentralverkauf'             => $availability->Zentralverkauf,
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
		$countCategoriesRecords = count($this->processedCategories);
		$countProperties = count($this->processedProperties);

		if ($countItemsBases > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countItemsBases items base records ...");

			DBQuery::getInstance()->insert('INSERT INTO `ItemsBase`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedItemsBases));
			DBQuery::getInstance()->insert('INSERT INTO `ItemsAvailability`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedAvailabilityRecords));
		}

		if ($countAttributeValueSets > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countAttributeValueSets attribute value set records ...");

			DBQuery::getInstance()->delete('DELETE FROM `AttributeValueSets` WHERE `ItemID` IN (\'' . implode('\',\'', array_keys($this->processedItemsBases)) . '\')');
			DBQuery::getInstance()->insert('INSERT INTO `AttributeValueSets`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedAttributeValueSets));
		}

		if ($countCategoriesRecords > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countCategoriesRecords records of categories ...");

			DBQuery::getInstance()->delete('DELETE FROM `ItemsCategories` WHERE `ItemID` IN (\'' . implode('\',\'', array_keys($this->processedItemsBases)) . '\')');
			DBQuery::getInstance()->insert('INSERT INTO `ItemsCategories`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedCategories));
		}

		if ($countProperties > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countProperties records of properties ...");

			DBQuery::getInstance()->delete('DELETE FROM `ItemsProperties` WHERE `ItemID` IN (\'' . implode('\',\'', array_keys($this->processedItemsBases)) . '\')');
			DBQuery::getInstance()->insert('INSERT INTO `ItemsProperties`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedProperties));
		}
	}

}
