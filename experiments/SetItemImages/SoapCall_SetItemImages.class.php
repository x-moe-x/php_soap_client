<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetItemImages.class.php';

/**
 * Class SoapCall_SetItemImages
 */
class SoapCall_SetItemImages extends PlentySoapCall
{

	/**
	 * @var string
	 */
	const LOCAL_OPTIMIZED_IMAGE_PATH = '/kunden/homepages/22/d66025481/htdocs/plenty_item_images_optimised';

	/**
	 * @var RequestContainer_SetItemImages[]
	 */
	private $preparedRequests;

	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->preparedRequests = array();
	}

	/**
	 *
	 */
	public function execute()
	{
		// fetch all image url records
		$dbResult = DBQuery::getInstance()->select('SELECT ItemID, ImageID, ImageURL FROM ItemsImages WHERE ItemID = 79');

		$this->debug(__FUNCTION__ . ': found ' . $dbResult->getNumRows() . ' item image url records');

		// process them indiviually
		$i = 0;
		while ($currentItem = $dbResult->fetchAssoc())
		{
			if ($i % 100 === 0)
			{
				$this->getLogger()->info(__FUNCTION__ . ': processing record ' . $i);
			}
			$i++;

			// match against relPath and fName pattern
			if (preg_match("/^.+:\\/\\/.*?(?'relPath'\\/.*?\\/)(?'fName'[\\w\\d-+_]+\\.\\w+)$/", $currentItem['ImageURL'], $matches))
			{
				// check path if there's an optimised image...
				$path = self::LOCAL_OPTIMIZED_IMAGE_PATH . '/' . $matches['relPath'];
				if (file_exists($path . '/' . $matches['fName']))
				{
					// ... then prepare upload
					if (!array_key_exists($currentItem['ItemID'], $this->preparedRequests))
					{
						$this->preparedRequests[$currentItem['ItemID']] = new RequestContainer_SetItemImages();
					}
					$this->preparedRequests[$currentItem['ItemID']]->add($currentItem);
				}
			} // or terminate execution on error
			else
			{
				$this->getLogger()->crit(__FUNCTION__ . ': could not match' . $currentItem['ImageURL'] . ' against relPath & fName Pattern');
				die();
			}
		}

		// perform upload
	}
}
