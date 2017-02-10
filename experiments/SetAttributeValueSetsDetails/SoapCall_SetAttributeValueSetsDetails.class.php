<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once "RequestContainer_SetAttributeValueSetsDetails.class.php";

class SoapCall_SetAttributeValueSetsDetails extends PlentySoapCall {

	const MAX_ITEMS_PER_PAGES = 50;

	public function execute() {
		try {
			// get all free text fields for items to be written
			$oDBResult = DBQuery::getInstance()
				->select('SELECT * FROM `SetAttributeValueSetsDetails`');

			// for every 50 Items ...
			for ($page = 0, $maxPage = ceil($oDBResult->getNumRows() / self::MAX_ITEMS_PER_PAGES); $page < $maxPage; $page++) {
				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_SetAttributeValueSetsDetails(self::MAX_ITEMS_PER_PAGES);

				// ... fill in data
				while (!$requestContainer->isFull() && ($currentData = $oDBResult->fetchAssoc())) {
					$requestContainer->add($currentData);
				}

				$this->debug(__FUNCTION__ . ' writing page ' . ($page + 1) . ' of ' . $maxPage);

				// do soap call to plenty
				$response = $this->getPlentySoap()
					->SetAttributeValueSetsDetails($requestContainer->getRequest());

				// ... if successful ...
				if ($response->Success == true) {
					// ... be quiet ...
				} else {
					// ... otherwise log error and try next request
					$this->getLogger()
						->debug(__FUNCTION__ . ' Request Error (check for empty data in one product spoiling the whole page)');
				}
			}
		} catch (Exception $e) {
			$this->onExceptionAction($e);
		}
	}
}