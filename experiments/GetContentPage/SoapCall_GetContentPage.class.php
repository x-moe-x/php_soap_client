<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetContentPage.class.php';

function get_duplicates($array) {
	return array_unique(array_diff_assoc($array, array_unique($array)));
}

class SoapCall_GetContentPage extends PlentySoapCall {

	/**
	 * @var array
	 */
	private $aIndexPageList;

	/**
	 * @var array
	 */
	private $aIndexList;

	/**
	 * @var array
	 */
	private $aWriteBackData;

	/**
	 * @var array
	 */
	private $aErrorList;

	/**
	 * @var string
	 */
	private $language;

	public function __construct() {
		libxml_use_internal_errors(true);

		$this -> language = 'de';

		$this -> aIndexPageList = array(278, 367);
		$this -> aIndexList = array();
		$this -> aWriteBackData = array();
		$this -> aErrorList = array();
	}

	/**
	 * @return void
	 */
	public function execute() {
		$oRequest_GetContentPage = new Request_GetContentPage();
		try {
			foreach ($this -> aIndexPageList as $indexPageID) {
				$oPlentySoapResponse_GetIndexPage = $this -> getPlentySoap() -> GetContentPage($oRequest_GetContentPage -> getRequest($indexPageID, $this -> language));

				if ($oPlentySoapResponse_GetIndexPage -> Success == true) {
					$this -> parseIndexPage($oPlentySoapResponse_GetIndexPage);
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}
			sort($this -> aIndexList);

			$this -> getLogger() -> info(__FUNCTION__ . ' processing ' . count($this -> aIndexList) . ' content pages');

			foreach ($this-> aIndexList as $pageID) {
				$oPlentySoapResponse_GetContentPage = $this -> getPlentySoap() -> GetContentPage($oRequest_GetContentPage -> getRequest($pageID, $this -> language));
				if ($oPlentySoapResponse_GetContentPage -> Success == true) {
					if (isset($oPlentySoapResponse_GetContentPage -> ContentPage -> MainPage) && !empty($oPlentySoapResponse_GetContentPage -> ContentPage -> MainPage)) {
						$this -> parseContentPage($oPlentySoapResponse_GetContentPage);
					} else {
						$this -> aErrorList[] = array('contentPageID' => $pageID, 'error' => 'page is empty');
					}
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Request Error');
				}
			}

			// if there's just a single error ...
			if (count($this -> aErrorList) !== 0) {
				print_r($this -> aErrorList);

				// ... prevent script execution
				die();
			}
			
			$writtenPages = 0;
			// ... otherwise ...
			foreach ($this-> aWriteBackData as $oPlentySoapObject_ContentPage) {

				$oPlentySoapRequest_SetContentPage = new PlentySoapRequest_SetContentPage();
				$oPlentySoapRequest_SetContentPage -> Lang = $this -> language;
				$oPlentySoapRequest_SetContentPage -> ContentPage = $oPlentySoapObject_ContentPage;

				// ... write back data
				$oPlentySoapResponse_SetContentPage = $this -> getPlentySoap() -> SetContentPage($oPlentySoapRequest_SetContentPage);

				if ($oPlentySoapResponse_SetContentPage -> Success == true) {
					$writtenPages++;					
				} else {
					$this -> getLogger() -> debug(__FUNCTION__ . ' Writeback Request Error');
				}
			}
			
			$this -> getLogger() -> info(__FUNCTION__ . " written $writtenPages pages");			
			
		} catch(Exception $e) {
			$this -> onExceptionAction($e);
		}
	}

	private function parseContentPage(PlentySoapResponse_GetContentPage $oPlentySoapResponse_GetContentPage) {
		// create document
		$dom = new DOMDocument();
		$dom -> loadHTML($oPlentySoapResponse_GetContentPage -> ContentPage -> MainPage);

		// handle parsing errors
		foreach (libxml_get_errors() as $error) {
			$this -> aErrorList[] = array('contentPageID' => $oPlentySoapResponse_GetContentPage -> ContentPage -> ContentPageID, 'error' => 'line: ' . $error -> line . ': ' . $error -> message);
		}

		libxml_clear_errors();

		$finder = new DomXPath($dom);

		// for every 'glossarCategoryList' - element ...
		$glossarCategoryNodes = $finder -> query("//div[@class='glossarCategoryList']/ul/li");
		foreach ($glossarCategoryNodes as $node) {

			// ... find corresponding links ...
			$linkNodes = $finder -> query("div/a", $node);

			if ($linkNodes -> length === 2) {
				// ... and substitute missing hrefs
				$href = $linkNodes -> item(0) -> getAttribute('href');
				$linkNodes -> item(1) -> setAttribute('href', $href);

				// basic error checking
				if (is_null($href)) {
					throw new RuntimeException("href = null for content page id = " . $oPlentySoapResponse_GetContentPage -> ContentPage -> ContentPageID);
				}
			} else if ($linkNodes -> length === 1) {
				$this -> aErrorList[] = array('contentPageID' => $oPlentySoapResponse_GetContentPage -> ContentPage -> ContentPageID, 'error' => "found just one link (href = " . $linkNodes -> item(0) -> getAttribute('href') . ")");
				return;
			} else {
				$this -> aErrorList[] = array('contentPageID' => $oPlentySoapResponse_GetContentPage -> ContentPage -> ContentPageID, 'error' => "found " . $linkNodes -> length . " links");
				return;				
			}

		}

		// write back sanitized results
		$sanitizedMainPage = "<!-- begin main-content div -->\n" . preg_replace("/(?:%5B(?<href>LinkTo_Cat(?:_\d+)+)%5D)/", '[$1]', tidy_repair_string($dom -> saveHTML(), array('indent' => true, 'doctype' => 'omit', 'show-body-only' => true, 'fix-uri' => false, 'indent-spaces' => 4, 'sort-attributes' => 'alpha', 'wrap' => 0)));
				
		$oPlentySoapResponse_GetContentPage -> ContentPage -> MainPage = preg_replace("/    /", "\t", $sanitizedMainPage);

		// store data for write back operation
		$this -> aWriteBackData[] = $oPlentySoapResponse_GetContentPage -> ContentPage;

		$this -> getLogger(__FUNCTION__ . " done");
	}

	private function parseIndexPage(PlentySoapResponse_GetContentPage $oPlentySoapResponse_GetIndexPage) {
		$this -> getLogger() -> info(__FUNCTION__ . " processing index page " . $oPlentySoapResponse_GetIndexPage -> ContentPage -> ContentPageID);
		if (preg_match_all("/^\s*\\{url:'\\[Page_(?<pageID>\\d+)\\]', id:\\1, name:'.*'\\},?$/m", $oPlentySoapResponse_GetIndexPage -> ContentPage -> MainPage, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$this -> aIndexList[] = $match['pageID'];
			}
		}
	}

}
?>