<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsBase.class.php';
require_once ROOT.'includes/DBLastUpdate.php';



class SoapCall_GetItemsBase extends PlentySoapCall
{
	private $page								=	0;
	private $pages								=	-1;
	private $startAtPage = 0;
	private $oPlentySoapRequest_GetItemsBase	=	null;

	/// db-function name to store corresponding last update timestamps
	private $functionName						=	'GetItemsBase';

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__);

		list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart($this->functionName);

		if ($this->pages == -1)
		{
			try
			{

				$oRequest_GetItemsBase = new Request_GetItemsBase();

				$this->oPlentySoapRequest_GetItemsBase = $oRequest_GetItemsBase->getRequest($lastUpdate, $currentTime, $this->startAtPage);

				if ($this -> startAtPage > 0) {
					$this -> getLogger() -> debug(__FUNCTION__ . " Starting at page " . $this -> startAtPage);
				}

				/*
				 * do soap call
				*/
				$response		=	$this->getPlentySoap()->GetItemsBase($this->oPlentySoapRequest_GetItemsBase);

				if( $response->Success == true )
				{
					$articlesFound		= 	count($response->ItemsBase->item);
					$pagesFound			=	$response->Pages;

					$this->getLogger()->debug(__FUNCTION__.' Request Success - articles found : '.$articlesFound .' / pages : '.$pagesFound );

					// process response
					$this->responseInterpretation($response);

					if ( $pagesFound > $this->page )
					{
						$this->page		=	$this->startAtPage +1;
						$this->pages	=	$pagesFound;

						lastUpdatePageUpdate($this -> functionName, $this -> page);
						$this->executePages();

					}
				}
				else
				{
					if (count($response->ErrorMessages->item) > 0)
					{
						foreach($response->ErrorMessages->item as $item)
						{
							if ($item->Code == "EIB0004")
								$this->getLogger()->debug(__FUNCTION__.' No new ItemsBase data available');
						}
					}
					else
						$this->getLogger()->debug(__FUNCTION__.' Request Error');
				}
			}
			catch(Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
		else
		{
			$this->executePages();
		}

		lastUpdateFinish($currentTime,$this->functionName);
	}

	private function responseInterpretation($oPlentySoapResponse_GetItemsBase)
	{
		if( is_array( $oPlentySoapResponse_GetItemsBase->ItemsBase->item ) )
		{
			foreach( $oPlentySoapResponse_GetItemsBase->ItemsBase->item AS $itemsBase)
			{
				$this->processItemsBase($itemsBase);
			}
		}
		else
		{
			$this->processItemsBase($oPlentySoapResponse_GetItemsBase->Orders->item);
		}
		$this->getLogger()->debug(__FUNCTION__.' : done' );
	}

	private function processAttributeValueSet($oItemID, $oAttributeValueSet){
		$this->getLogger()->debug(__FUNCTION__.' : '
				. 	' AttributeValueSetID : '			.$oAttributeValueSet->AttributeValueSetID		.','
				. 	' AttributeValueSetName : '				.$oAttributeValueSet->AttributeValueSetName
		);

		// store AttributeValueSets to DB
		$query = 'REPLACE INTO `AttributeValueSets` '.
			DBUtils::buildInsert(
				array(
					'ItemID'				=> $oItemID,
					'AttributeValueSetID'	=> $oAttributeValueSet->AttributeValueSetID,
					'AttributeValueSetName'	=> $oAttributeValueSet->AttributeValueSetName,
					'Availability'			=> $oAttributeValueSet->Availability,
					'EAN'					=> $oAttributeValueSet->EAN,
					'EAN2'					=> $oAttributeValueSet->EAN2,
					'EAN3'					=> $oAttributeValueSet->EAN3,
					'EAN4'					=> $oAttributeValueSet->EAN4,
					'ASIN'					=> $oAttributeValueSet->ASIN,
					'ColliNo'				=> $oAttributeValueSet->ColliNo,
					'PriceID'				=> $oAttributeValueSet->PriceID,
					'PurchasePrice'			=> $oAttributeValueSet->PurchasePrice
				)
			);

			DBQuery::getInstance()->replace($query);
	}

	private function processItemsBase($oItemsBase)
	{
		$this->getLogger()->debug(__FUNCTION__.' : '
				. 	' ItemID : '			.$oItemsBase->ItemID		.','
				. 	' ItemNo : '			.$oItemsBase->ItemNo		.','
				. 	' Name : '				.$oItemsBase->Texts->Name
		);

		// store ItemsBase into DB
		$query = 'REPLACE INTO `ItemsBase` '.
				DBUtils::buildInsert(
						array(
								'ASIN'						=> $oItemsBase->ASIN,
							/*	'AttributeValueSets'		=> $oItemsBase->AttributeValueSets,	skipped here and stored to separate table	*/
							/*	'Availability'				=> $oItemsBase->Availability,	currently considered irrelevant	*/
								'BundleType'				=> $oItemsBase->BundleType,
							/*	'Categories'				=> $oItemsBase->Categories,	ignored since not part of the request	*/
								'Condition'					=> $oItemsBase->Condition,
								'CustomsTariffNumber'		=> $oItemsBase->CustomsTariffNumber,
								'DeepLink'					=> $oItemsBase->DeepLink,
								'EAN1'						=> $oItemsBase->EAN1,
								'EAN2'						=> $oItemsBase->EAN2,
								'EAN3'						=> $oItemsBase->EAN3,
								'EAN4'						=> $oItemsBase->EAN4,
								'EbayEPID'					=> $oItemsBase->EbayEPID,
								'ExternalItemID'			=> $oItemsBase->ExternalItemID,
								'FSK'						=> $oItemsBase->FSK,
							/*	'FreeTextFields'			=> $oItemsBase->FreeTextFields,	replaced with it's subitems	*/
								'Free1'						=> $oItemsBase->FreeTextFields->Free1,
								'Free2'						=> $oItemsBase->FreeTextFields->Free2,
								'Free3'						=> $oItemsBase->FreeTextFields->Free3,
								'Free4'						=> $oItemsBase->FreeTextFields->Free4,
								'Free5'						=> $oItemsBase->FreeTextFields->Free5,
								'Free6'						=> $oItemsBase->FreeTextFields->Free6,
								'Free7'						=> $oItemsBase->FreeTextFields->Free7,
								'Free8'						=> $oItemsBase->FreeTextFields->Free8,
								'Free9'						=> $oItemsBase->FreeTextFields->Free9,
								'Free10'					=> $oItemsBase->FreeTextFields->Free10,
								'Free11'					=> $oItemsBase->FreeTextFields->Free11,
								'Free12'					=> $oItemsBase->FreeTextFields->Free12,
								'Free13'					=> $oItemsBase->FreeTextFields->Free13,
								'Free14'					=> $oItemsBase->FreeTextFields->Free14,
								'Free15'					=> $oItemsBase->FreeTextFields->Free15,
								'Free16'					=> $oItemsBase->FreeTextFields->Free16,
								'Free17'					=> $oItemsBase->FreeTextFields->Free17,
								'Free18'					=> $oItemsBase->FreeTextFields->Free18,
								'Free19'					=> $oItemsBase->FreeTextFields->Free19,
								'Free20'					=> $oItemsBase->FreeTextFields->Free20,
								'HasAttributes'				=> $oItemsBase->HasAttributes,
								'ISBN'						=> $oItemsBase->ISBN,
								'Inserted'					=> $oItemsBase->Inserted,
							/*	'ItemAttributeMarkup'		=> $oItemsBase->ItemAttributeMarkup,	ignored since not part of the request	*/
								'ItemID'					=> $oItemsBase->ItemID,
								'ItemNo'					=> $oItemsBase->ItemNo,
							/*	'ItemProperties'			=> $oItemsBase->ItemProperties,	ignored since not part of the request	*/
							/*	'ItemSuppliers'				=> $oItemsBase->ItemSuppliers,	ignored since not part of the request	*/
							/*	'ItemURL'					=> $oItemsBase->ItemURL,	ignored since not part of the request	*/
								'LastUpdate'				=> $oItemsBase->LastUpdate,
								'Marking1ID'				=> $oItemsBase->Marking1ID,
								'Marking2ID'				=> $oItemsBase->Marking2ID,
								'Model'						=> $oItemsBase->Model,
							/*	'Others'					=> $oItemsBase->Others,	ignored since not part of the request	*/
							/*	'ParcelServicePresetIDs'	=> $oItemsBase->ParcelServicePresetIDs,*/
							/*	'PriceSet'					=> $oItemsBase->PriceSet,	currently considered irrelevant	*/
								'ProducerID'				=> $oItemsBase->ProducerID,
								'ProducingCountryID'		=> $oItemsBase->ProducingCountryID,
								'Published'					=> $oItemsBase->Published,
							/*	'Stock'						=> $oItemsBase->Stock,	currently considered irrelevant	*/
								'StorageLocation'			=> $oItemsBase->StorageLocation,
							/*	'Texts'						=> $oItemsBase->Texts,	replaced with it's subitems	*/
								'Keywords'					=> $oItemsBase->Texts->Keywords,
								'Lang'						=> $oItemsBase->Texts->Lang,
								'LongDescription'			=> $oItemsBase->Texts->LongDescription,
								'MetaDescription'			=> $oItemsBase->Texts->MetaDescription,
								'Name'						=> $oItemsBase->Texts->Name,
								'Name2'						=> $oItemsBase->Texts->Name2,
								'Name3'						=> $oItemsBase->Texts->Name3,
								'ShortDescription'			=> $oItemsBase->Texts->ShortDescription,
								'TechnicalData'				=> $oItemsBase->Texts->TechnicalData,
							/*
							 *	end of Texts' replacement 
							 */
								'Type'						=> $oItemsBase->Type,
								'VATInternalID'				=> $oItemsBase->VATInternalID,
								'WebShopSpecial'			=> $oItemsBase->WebShopSpecial
						)
				);

		DBQuery::getInstance()->replace($query);

		// delete old entrys from AttributeValueSets to prevent unrecognized deletes
		$query = 'DELETE FROM `AttributeValueSets` WHERE `ItemID` = ' . $oItemsBase->ItemID;
		DBQuery::getInstance()->delete($query);

		// process AttributeValueSets
		if ($oItemsBase->HasAttributes){
			if (is_array($oItemsBase->AttributeValueSets->item)){
				foreach ($oItemsBase->AttributeValueSets->item as $attributeValueSet) {
					$this->processAttributeValueSet($oItemsBase->ItemID, $attributeValueSet);
				}
			}else{
				$this->processAttributeValueSet($oItemsBase->ItemID, $oItemsBase->AttributeValueSets->item);
			}
		}
	}

	private function executePages()
	{
		while ( $this->pages > $this->page )
		{
			$this->oPlentySoapRequest_GetItemsBase->Page = $this->page;
			try
			{
				$response		=	$this->getPlentySoap()->GetItemsBase( $this->oPlentySoapRequest_GetItemsBase );

				if( $response->Success == true )
				{
					$articlesFound	=	count($response->ItemsBase->item);
					$this->getLogger()->debug(__FUNCTION__.' Request Success - articles found : '.$articlesFound .' / page : '.$this->page );

					// auswerten
					$this->responseInterpretation( $response);
				}

				$this->page++;
				lastUpdatePageUpdate($this -> functionName, $this -> page);

			}
			catch(Exception $e)
			{
				$this->onExceptionAction($e);
			}

			// TODO remove after debugging:
			// stop after 3 pages
			if ($this -> page - $this -> startAtPage >= 3)
				break;

		}
	}
}

?>