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
	const LOCAL_IMAGE_PATH = '/kunden/homepages/22/d66025481/htdocs/upload_images';

	/**
	 * @var int
	 */
	const MAX_IMAGES_PER_REQUEST = 10;

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
		// fetch all image update records
		$dbResult = DBQuery::getInstance()
			->select('SELECT * FROM SetItemImages');

		$this->debug(__FUNCTION__ . ': found ' . $dbResult->getNumRows() . ' item image records');

		// process them indiviually
		$i = 0;
		while ($currentItem = $dbResult->fetchAssoc())
		{
			if ($i % 50 === 0)
			{
				$this->getLogger()
					->info(__FUNCTION__ . ': processing record ' . $i);
			}
			$i++;

			$itemId = intval($currentItem['ItemID']);
			if (!array_key_exists($itemId, $this->preparedRequests)) {
				$this->preparedRequests[$itemId] = new RequestContainer_SetItemImages($itemId, self::MAX_IMAGES_PER_REQUEST);
			}

			// prepare an update
			$data = array(
				'Position'         => intval($currentItem['Position']),
				'AttributeValueId' => $currentItem['AttributeValueId'],
				'ImageID'          => $currentItem['ImageID'],
				'ImageFileName'    => $currentItem['ImageFileName'],
				'ImageFileEnding'  => $currentItem['ImageFileEnding'],
				'ImageURL'         => $currentItem['ImageURL'],
				'AlternativeText'  => $currentItem['AlternativeText'],
				'Name'             => $currentItem['Name'],
				'Lang'             => $currentItem['Lang'],
				'DeleteName'       => $currentItem['DeleteName'],
				'ImageFilePath'    => null,
			);

			if (!is_null($currentItem['ImageFileLocalPath'])) {
				// ... then also prepare upload
				$path = self::LOCAL_IMAGE_PATH . '/' . $currentItem['ImageFileLocalPath'];
				if (file_exists($path)) {
					$data['ImageFilePath'] = $path;
				} else {
					throw new RuntimeException("Missing File for ItemID $itemId: $path");
				}
			}

			$this->preparedRequests[$itemId]->add($data);
		}

		// perform upload/update
		try {
			foreach ($this->preparedRequests as $request) {
				$response = $this->getPlentySoap()
					->SetItemImages($request->getRequest());
				if ($response->Success) {
					// do nothing
				} else {
					print_r($response);
					die();
				}

			}
		} catch
		(Exception $e) {
			$this->onExceptionAction($e);
		}
	}
}
