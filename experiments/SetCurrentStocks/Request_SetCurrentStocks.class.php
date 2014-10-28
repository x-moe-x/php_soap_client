<?php

require_once ROOT . 'includes/FillObjectFromArray.php';

class Request_SetCurrentStocks {

	/**
	 * @return Request_SetCurrentStocks
	 */
	public function __construct() {
	}

	/**
	 * @return PlentySoapRequest_SetCurrentStocks
	 */
	public function getRequest() {
		$oPlentySoapRequest_SetCurrentStocks = new PlentySoapRequest_SetCurrentStocks();

		return $oPlentySoapRequest_SetCurrentStocks;
	}

}
?>