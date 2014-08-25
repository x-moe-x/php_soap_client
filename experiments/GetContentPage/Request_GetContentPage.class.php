<?php
class Request_GetContentPage {

	/**
	 * @param int $contentPageID
	 */
	public function getRequest($contentPageID, $language) {
		$oPlentySoapRequest_GetContentPage = new PlentySoapRequest_GetContentPage();

		$oPlentySoapRequest_GetContentPage -> ContentPageID = $contentPageID;
		$oPlentySoapRequest_GetContentPage -> Lang = $language;

		return $oPlentySoapRequest_GetContentPage;
	}

}
?>