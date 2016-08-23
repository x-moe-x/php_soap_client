<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetItemsBase.class.php';

/**
 * Class SoapCall_SetItemsBase
 */
class SoapCall_SetItemsBase extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_ITEMS_PER_PAGES = 50;

	/**
	 * @return SoapCall_SetItemsBase
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
		$this->debug(__FUNCTION__ . ' writing ItemsBase...');
		try
		{
			// get all free text fields for items to be written
			$oDBResult = DBQuery::getInstance()->select('SELECT * FROM `SetItemsBase`');

			// for every 50 Items ...
			for ($page = 0, $maxPage = ceil($oDBResult->getNumRows() / self::MAX_ITEMS_PER_PAGES); $page < $maxPage; $page++)
			{
				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_SetItemsBase();

				// ... fill in data
				while (!$requestContainer->isFull() && ($currentItemBase = $oDBResult->fetchAssoc()))
				{
					$requestContainer->add($currentItemBase);
				}

				$this->debug(__FUNCTION__ . ' writing page ' . ($page + 1) . ' of ' . $maxPage);

				// do soap call to plenty
				$response = $this->getPlentySoap()->SetItemsBase($requestContainer->getRequest());

				// ... if successful ...
				if ($response->Success == true)
				{
					// ... be quiet ...
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