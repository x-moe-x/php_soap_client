<?php

require_once ROOT . 'includes/RequestContainer.class.php';
require_once ROOT . 'includes/FillObjectFromArray.php';

/**
 * Class RequestContainer_SetItemImages
 */
class RequestContainer_SetItemImages extends RequestContainer
{

	/**
	 * @var int
	 */
	private $itemId;

	public function __construct($itemId, $capacity) {
		parent::__construct($capacity);
		$this->itemId = $itemId;
	}

	/**
	 * returns the assembled request
	 * @return PlentySoapRequest_SetItemImages
	 */
	public function getRequest()
	{
		// prepare new request ...
		$result = new PlentySoapRequest_SetItemImages();

		// ... for specific item id
		$result->ItemID = $this->itemId;

		$result->Images = new ArrayOfPlentysoaprequestobject_setitemimagesimage();
		$result->Images->item = array();

		// ... to update/upload images ...
		foreach ($this->items as $aItem) {
			// prepare plain image data
			$image = new PlentySoapRequestObject_SetItemImagesImage();
			fillObjectFromArray($image, array(
				'AttributeValueId' => $aItem['AttributeValueId'],
				// if path is given, then prepare upload, otherwise an update of all given data
				'ImageFileData'    => is_null($aItem['ImageFilePath']) ? null : base64_encode(file_get_contents($aItem['ImageFilePath'])),
				'ImageFileEnding'  => $aItem['ImageFileEnding'],
				'ImageFileName'    => $aItem['ImageFileName'],
				'ImageID'          => $aItem['ImageID'],
				'ImageURL'         => $aItem['ImageURL'],
				'Position'         => $aItem['Position'],
			));

			// prepare name data if necessary
			if (!is_null($aItem['Name']) || !is_null($aItem['AlternativeText']) || !is_null($aItem['DeleteName'])) {
				$image->Names = new ArrayOfPlentysoaprequestobject_setitemimagesimagename();
				$image->Names->item = array();

				$nameObject = new PlentySoapRequestObject_SetItemImagesImageName();
				fillObjectFromArray($nameObject, array(
					'Name'            => $aItem['Name'],
					'AlternativeText' => $aItem['AlternativeText'],
					'Lang'            => $aItem['Lang'],
					'DeleteName'      => $aItem['DeleteName'],
				));

				$image->Names->item[] = $nameObject;
			}
			$result->Images->item[] = $image;
		}
		return $result;
	}
}
