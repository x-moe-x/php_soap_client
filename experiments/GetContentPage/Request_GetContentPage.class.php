<?php
class Request_GetContentPage {

	/**
	 * Prepares a request for a specific content page in a specific language
	 *
	 * @param int $contentPageID the content page's numeric id
	 * @param string $language the language's shortcut
	 *
	 * @return PlentySoapRequest_GetContentPage
	 */
	public static function getRequest($contentPageID, $language = 'de') {
		$oPlentySoapRequest_GetContentPage = new PlentySoapRequest_GetContentPage();

		$oPlentySoapRequest_GetContentPage -> ContentPageID = $contentPageID;
		$oPlentySoapRequest_GetContentPage -> Lang = $language;

		return $oPlentySoapRequest_GetContentPage;
	}

}
?>