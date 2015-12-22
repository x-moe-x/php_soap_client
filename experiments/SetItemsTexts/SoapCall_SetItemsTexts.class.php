<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetItemsTexts.class.php';

/**
 * Class SoapCall_SetItemsTexts
 */
class SoapCall_SetItemsTexts extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_ITEMS_PER_PAGES = 100;

	/**
	 * @return SoapCall_SetItemsTexts
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute()
	{
		$this->debug(__FUNCTION__ . ' writing ItemsTexts...');
		try
		{
			// get all texts for items to be written
			$oDBResult = DBQuery::getInstance()->select('SELECT * FROM `SetItemsTexts`');

			// for every 100 Items ...
			for ($page = 0, $maxPage = ceil($oDBResult->getNumRows() / self::MAX_ITEMS_PER_PAGES); $page < $maxPage; $page++)
			{
				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_SetItemsTexts();

				// ... fill in data
				while (!$requestContainer->isFull() && ($currentTextData = $oDBResult->fetchAssoc()))
				{
					$requestContainer->add($currentTextData);
				}

				$this->debug(__FUNCTION__ . ' writing page ' . ($page + 1) . ' of ' . $maxPage);

				// do soap call to plenty
				$response = $this->getPlentySoap()->SetItemsTexts($requestContainer->getRequest());

				// ... if successful ...
				if ($response->Success == true)
				{
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
}
