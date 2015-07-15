<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_GetItemsTexts.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class SoapCall_GetItemsTexts
 */
class SoapCall_GetItemsTexts extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_ITEMS_TEXTS_PER_PAGE = 100;

	/**
	 * @var array
	 */
	private $itemsTextsData;

	/**
	 * @return SoapCall_GetItemsTexts
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->itemsTextsData = array();
	}

	public function execute()
	{
		$this->debug(__FUNCTION__ . ' retrieving ItemsTexts...');
		try
		{
			// get all itemIDs
			$itemIdDbResult = DBQuery::getInstance()->select('SELECT ItemID FROM ItemsBase');

			// for every 100 Items ...
			for ($page = 0, $maxPage = ceil($itemIdDbResult->getNumRows() / self::MAX_ITEMS_TEXTS_PER_PAGE); $page < $maxPage; $page++)
			{
				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_GetItemsTexts(self::MAX_ITEMS_TEXTS_PER_PAGE);

				// ... fill in data
				while (!$requestContainer->isFull() && ($currentTextData = $itemIdDbResult->fetchAssoc()))
				{
					$requestContainer->add($currentTextData['ItemID']);
				}

				$this->debug(__FUNCTION__ . ' reading page ' . ($page + 1) . ' of ' . $maxPage);

				// do soap call to plenty
				$response = $this->getPlentySoap()->GetItemsTexts($requestContainer->getRequest());

				// ... if successful ...
				if ($response->Success == true)
				{
					$this->responseInterpretation($response);
				} else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
				}
			}
			$this->storeToDB();
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @param PlentySoapResponse_GetItemsTexts $response
	 */
	private function responseInterpretation($response)
	{
		if (is_array($response->ItemTexts->item))
		{

			foreach ($response->ItemTexts->item AS $itemsText)
			{
				$this->processItemsText($itemsText);
			}
		} else
		{
			$this->processItemsText($response->ItemTexts->item);
		}
	}

	/**
	 * @param PlentySoapObject_GetItemsTexts $itemsText
	 */
	private function processItemsText($itemsText)
	{
		$this->itemsTextsData[] = array(
			'ItemID'                  => $itemsText->ItemID,
			'Name'                    => $itemsText->Name,
			'Name2'                   => $itemsText->Name2,
			'Name3'                   => $itemsText->Name3,
			'ShortDescription'        => $itemsText->ShortDescription,
			'LongDescription'         => $itemsText->LongDescription,
			'MetaDescription'         => $itemsText->MetaDescription,
			'TechnicalData'           => $itemsText->TechnicalData,
			'ItemDescriptionKeywords' => $itemsText->ItemDescriptionKeywords,
			'Lang'                    => $itemsText->Lang,
			'UrlContent'              => $itemsText->UrlContent,
		);
	}

	/**
	 *
	 */
	private function storeToDB()
	{
		$countItemsTexts = count($this->itemsTextsData);

		if ($countItemsTexts > 0)
		{
			$this->debug(__FUNCTION__ . " writing $countItemsTexts records of items texts");
			DBQuery::getInstance()->truncate('TRUNCATE ItemsTexts');
			DBQuery::getInstance()->insert('INSERT INTO ItemsTexts' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->itemsTextsData));
		}
	}
}
