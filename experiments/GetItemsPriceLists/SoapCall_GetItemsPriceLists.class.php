<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_GetItemsPriceLists.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class SoapCall_GetItemsPriceLists
 */
class SoapCall_GetItemsPriceLists extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_PRICE_SETS_PER_PAGE = 200;

	/**
	 * @var array
	 */
	private $priceSets;

	/**
	 * @return SoapCall_GetItemsPriceLists
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->priceSets = array();

		DBQuery::getInstance()->truncate('TRUNCATE TABLE `PriceSets`');
	}

	/**
	 * @return void
	 */
	public function execute()
	{
		try
		{
			// get all possible Item Variants
			$itemVariantsDbResult = DBQuery::getInstance()->select($this->getQuery());

			// for every 200 variants ...
			for ($page = 0, $maxPage = ceil($itemVariantsDbResult->getNumRows() / self::MAX_PRICE_SETS_PER_PAGE); $page < $maxPage; $page++)
			{

				// ... prepare a seperate request
				$preparedRequest = new RequestContainer_GetItemsPriceLists();
				while (!$preparedRequest->isFull() && $current = $itemVariantsDbResult->fetchAssoc())
				{
					$preparedRequest->add($current['ItemID'], $current['AttributeValueSetID']);
				}

				// ... then do the soap call ...
				$response = $this->getPlentySoap()->GetItemsPriceLists($preparedRequest->getRequest());

				// ... if successfull ...
				if ($response->Success == true)
				{

					// ... then process response
					$this->responseInterpretation($response);
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}

			// when done store all retrieved data to db
			$this->storeToDB();

		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @return string query
	 */
	private function getQuery()
	{
		return 'SELECT
	ItemsBase.ItemID,
	CASE WHEN (AttributeValueSets.AttributeValueSetID IS NULL) THEN
        "0"
    ELSE
        AttributeValueSets.AttributeValueSetID
    END AttributeValueSetID
FROM
	ItemsBase
LEFT JOIN AttributeValueSets
    ON ItemsBase.ItemID = AttributeValueSets.ItemID
ORDER BY
	ItemsBase.ItemID';
	}

	/**
	 * @param PlentySoapResponse_GetItemsPriceLists $response
	 *
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsPriceLists $response)
	{
		if (is_array($response->ItemsPriceList->item))
		{
			/** @var PlentySoapResponseObject_GetItemsPriceLists $itemsPriceList */
			foreach ($response->ItemsPriceList->item as $itemsPriceList)
			{
				if (is_array($itemsPriceList->ItemPriceSets->item))
				{
					/** @var PlentySoapObject_ItemPriceSet $itemPriceSet */
					foreach ($itemsPriceList->ItemPriceSets->item as $itemPriceSet)
					{
						$this->processPriceSet($itemsPriceList->ItemID, $itemPriceSet);
					}
				} else
				{
					$this->processPriceSet($itemsPriceList->ItemID, $itemsPriceList->ItemPriceSets->item);
				}
			}
		} else
		{
			if (is_array($response->ItemsPriceList->item->ItemPriceSets->item))
			{
				/** @var PlentySoapObject_ItemPriceSet $itemPriceSet */
				foreach ($response->ItemsPriceList->item->ItemPriceSets->item as $itemPriceSet)
				{
					$this->processPriceSet($response->ItemsPriceList->item->ItemID, $itemPriceSet);
				}
			} else
			{
				$this->processPriceSet($response->ItemsPriceList->item->ItemID, $response->ItemsPriceList->item->ItemPriceSets->item);
			}
		}
	}

	/**
	 * @param int                           $itemID
	 * @param PlentySoapObject_ItemPriceSet $itemPriceSet
	 *
	 * @return void
	 */
	private function processPriceSet($itemID, $itemPriceSet)
	{
		// check if price-set is new ...
		if (!array_key_exists($itemPriceSet->PriceID, $this->priceSets))
		{
			// ... then store data
			$this->priceSets[$itemPriceSet->PriceID] = array(
				'ItemID'             => $itemID,
				'PriceID'            => $itemPriceSet->PriceID,
				'Price'              => $itemPriceSet->Price,
				'Price1'             => $itemPriceSet->Price1,
				'Price2'             => $itemPriceSet->Price2,
				'Price3'             => $itemPriceSet->Price3,
				'Price4'             => $itemPriceSet->Price4,
				'Price5'             => $itemPriceSet->Price5,
				'Price6'             => $itemPriceSet->Price6,
				'Price7'             => $itemPriceSet->Price7,
				'Price8'             => $itemPriceSet->Price8,
				'Price9'             => $itemPriceSet->Price9,
				'Price10'            => $itemPriceSet->Price10,
				'Price11'            => $itemPriceSet->Price11,
				'Price12'            => $itemPriceSet->Price12,
				'Lot'                => $itemPriceSet->Lot,
				'Package'            => $itemPriceSet->Package,
				'PackagingUnit'      => $itemPriceSet->PackagingUnit,
				'Position'           => $itemPriceSet->Position,
				'PurchasePriceNet'   => $itemPriceSet->PurchasePriceNet,
				'RRP'                => $itemPriceSet->RRP,
				'RebateLevelPrice10' => $itemPriceSet->RebateLevelPrice10,
				'RebateLevelPrice11' => $itemPriceSet->RebateLevelPrice11,
				'RebateLevelPrice6'  => $itemPriceSet->RebateLevelPrice6,
				'RebateLevelPrice7'  => $itemPriceSet->RebateLevelPrice7,
				'RebateLevelPrice8'  => $itemPriceSet->RebateLevelPrice8,
				'RebateLevelPrice9'  => $itemPriceSet->RebateLevelPrice9,
				'ShowOnly'           => $itemPriceSet->ShowOnly,
				'TypeOfPackage'      => $itemPriceSet->TypeOfPackage,
				'Unit'               => $itemPriceSet->Unit,
				'Unit1'              => $itemPriceSet->Unit1,
				'Unit2'              => $itemPriceSet->Unit2,
				'UnitLoadDevice'     => $itemPriceSet->UnitLoadDevice,
				'VAT'                => $itemPriceSet->VAT,
				'WeightInGramm'      => $itemPriceSet->WeightInGramm,
				'HeightInMM'         => $itemPriceSet->HeightInMM,
				'LengthInMM'         => $itemPriceSet->LengthInMM,
				'WidthInMM'          => $itemPriceSet->WidthInMM
			);
		} else
		{
			// ... otherwise skip
		}
	}

	/**
	 * @return void
	 */
	private function storeToDB()
	{
		$priceSetCount = count($this->priceSets);
		if ($priceSetCount > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " storing $priceSetCount price sets");
			DBQuery::getInstance()->insert('INSERT INTO `PriceSets`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->priceSets));
		}
	}
}
