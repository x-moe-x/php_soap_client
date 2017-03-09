<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetItemsPurchasePrice.class.php';

class SoapCall_SetItemsPurchasePrice extends PlentySoapCall {

	/**
	 * @var int
	 */
	const MAX_ITEMS_PER_PAGES = 100;

	/**
	 * @return SoapCall_SetItemsPurchasePrice
	 */
	public function __construct() {
		parent::__construct(__CLASS__);
	}

	public function execute() {
		$this->debug(__FUNCTION__ . ' writing ItemsPurchasePrices...');
		try {
			// get all purchase prices to be written
			$oDBResult = DBQuery::getInstance()
				->select('SELECT * FROM `SetItemsPurchasePrice`');

			// for every 100 Items ...
			for ($page = 0, $maxPage = ceil($oDBResult->getNumRows() / self::MAX_ITEMS_PER_PAGES); $page < $maxPage; $page++) {
				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_SetItemsPurchasePrice(self::MAX_ITEMS_PER_PAGES);

				// ... fill in data
				while (!$requestContainer->isFull() && ($currentPurchasePrice = $oDBResult->fetchAssoc())) {
					$requestContainer->add($currentPurchasePrice);
				}


				$this->debug(__FUNCTION__ . ' writing page ' . ($page + 1) . ' of ' . $maxPage);

				// do soap call to plenty
				$response = $this->getPlentySoap()
					->SetItemsPurchasePrice($requestContainer->getRequest());

				// ... if successful ...
				if ($response->Success == true) {
					// ... be quiet ...
				} else {
					// ... otherwise log error and try next request
					$this->getLogger()
						->debug(__FUNCTION__ . ' Request Error');
				}
			}
		} catch
		(Exception $e) {
			$this->onExceptionAction($e);
		}
	}
}
