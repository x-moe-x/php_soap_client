<?php
require_once realpath(dirname(__FILE__) . '/../../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';

/**
 * Class GetItemsImagesDownload
 */
class GetItemsImagesDownload extends NX_Executable
{
	/**
	 * @var string
	 */
	const LOCAL_IMAGE_PATH = '/kunden/homepages/22/d66025481/htdocs/plenty_item_images';

	/**
	 * @var int
	 */
	const SAMPLE_RATE = 100;

	/**
	 * @var resource
	 */
	private $curl;

	/**
	 * @return GetItemsImagesDownload
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
	}

	function __destruct()
	{
		curl_close($this->curl);
	}

	/**
	 *
	 */
	public function execute()
	{
		// fetch all image url records TODO: remove record limit after debugging
		$dbResult = DBQuery::getInstance()->select('SELECT ImageURL, ItemID, ImageID FROM ItemsImages');

		$this->debug(__FUNCTION__ . ': found ' . $dbResult->getNumRows() . ' item image url records');

		// process them indiviually
		$i = 0;
		while ($currentItem = $dbResult->fetchAssoc()) {
			if ($i % self::SAMPLE_RATE === 0) {
				$this->getLogger()->info(__FUNCTION__ . ': processing record ' . $i);
			}
			$i++;

			// match against relPath and fName pattern
			if (preg_match("/^.+:\\/\\/.*?(?'relPath'\\/.*?\\/)(?'fName'[\\w\\d-+_]+\\.\\w+)$/", $currentItem['ImageURL'], $matches)) {
				// check path if there's already an image...
				$path = self::LOCAL_IMAGE_PATH . '/' . $matches['relPath'];
				if (file_exists($path . '/' . $matches['fName'])) {
					// ... then skip this image
					$this->getLogger()->info(__FUNCTION__ . ': skipping ' . $matches['relPath'] . $matches['fName']); // TODO remove output after debugging
					continue;
				} // check if path doesn't exist...
				elseif (!file_exists($path)) {
					// then create path
					//$this->getLogger()->info(__FUNCTION__ . ': creating ' . $path); // TODO remove output after debugging
					mkdir($path, 0777, true);
				}

				// if: skip image (for now... TODO: add override)

				// if not: get data from url ...
				//$this->getLogger()->info(__FUNCTION__ . ': fetching data for item ' . $currentItem['ItemID'] . ', image ' . $currentItem['ImageID'] . ': ' . $matches['relPath'] . $matches['fName']);

				curl_setopt($this->curl, CURLOPT_URL, $currentItem['ImageURL']);
				$jpegDataRaw = curl_exec($this->curl);

				// ... and put data to disk
				if (is_dir($path)) {
					file_put_contents($path . '/' . $matches['fName'], $jpegDataRaw);
				} else {
					$this->getLogger()->crit(__FUNCTION__ . ': ' . $path . ' is no directory');
				}

			} // or terminate execution on error
			else {
				$this->getLogger()->crit(__FUNCTION__ . ': could not match' . $currentItem['ImageURL'] . ' against relPath & fName Pattern');
				die();
			}
		}
	}
}
