<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsImages.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class SoapCall_GetItemsImages
 */
class SoapCall_GetItemsImages extends PlentySoapCall
{
	/**
	 * @var int
	 */
	private $page = 0;

	/**
	 * @var int
	 */
	private $pages = -1;

	/**
	 * @var int
	 */
	private $startAtPage = 0;

	/**
	 * @var PlentySoapRequest_GetItemsImages
	 */
	private $plentySoapRequest = null;

	/**
	 * @var array
	 */
	private $processedItemsImages = null;


	/**
	 * @return SoapCall_GetItemsImages
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
		$this->processedItemsImages = array();
	}

	/**
	 * @return void
	 */
	public function execute()
	{

		list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart(__CLASS__);
		$lastUpdate = 0;
		$this->startAtPage = 0;

		if ($this->pages == -1) {
			try {
				$this->plentySoapRequest = Request_GetItemsImages::getRequest($lastUpdate, $currentTime, $this->startAtPage);

				if ($this->startAtPage > 0) {
					$this->debug(__FUNCTION__ . " Starting at page " . $this->startAtPage);
				}

				/*
				 * do soap call
				 */
				/** @var PlentySoapResponse_GetItemsImages */
				$response = $this->getPlentySoap()->GetItemsImages($this->plentySoapRequest);

				if (($response->Success == true) && isset($response->ItemsImages)) {
					// request successful, processing data..
					//
					$pagesFound = $response->Pages;
					$this->debug(__FUNCTION__ . ' Request Success - images found : ' . count($response->ItemsImages->item) . ' / pages : ' . $pagesFound);

					// process response
					$this->responseInterpretation($response);

					if ($pagesFound > $this->page) {
						$this->page = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						lastUpdatePageUpdate(__CLASS__, $this->page);
						$this->executePages();

					}
				} else {
					if (($response->Success == true) && !isset($response->ItemsImages)) {
						// request successful, but no data to process
						$this->debug(__FUNCTION__ . ' Request Success -  but no matching images found');
					} else {
						$this->debug(__FUNCTION__ . ' Request Error');
					}
				}
			} catch (Exception $e) {
				$this->onExceptionAction($e);
			}
		} else {
			$this->executePages();
		}

		$this->storeToDB();
		lastUpdateFinish($currentTime, __CLASS__);
	}

	/**
	 * @param PlentySoapResponse_GetItemsImages $response
	 *
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsImages $response)
	{
		if (is_array($response->ItemsImages->item)) {

			foreach ($response->ItemsImages->item AS $itemsImage) {
				$this->processItemsImage($itemsImage);
			}
		} else {
			$this->processItemsImage($response->ItemsImages->item);
		}
	}

	/**
	 * @param PlentySoapResponse_ObjectItemImage $itemsImage
	 */
	private function processItemsImage($itemsImage)
	{
		$processedImage = array(
			'ItemID'     => $itemsImage->ItemID,
			'ImageID'    => $itemsImage->ImageID,
			'ImageURL'   => $itemsImage->ImageURL,
			'Position'   => $itemsImage->Position,
			'ImageType'  => $itemsImage->ImageType,
			'LastUpdate' => $itemsImage->LastUpdate,
			'UploadTime' => $itemsImage->UploadTime,
			//'Names'      => $itemsImage->Names,
			//'References' => $itemsImage->References
		);
		if (isset($itemsImage->Names) && isset($itemsImage->Names->item)){
			$processedImage['Names'] = serialize($itemsImage->Names->item);
		}
		if (isset($itemsImage->References) && isset($itemsImage->References->item)){
			$processedImage['References'] = serialize($itemsImage->References->item);
		}
		$this->processedItemsImages[] = $processedImage;
	}

	/**
	 * @return void
	 */
	private function executePages()
	{
		while ($this->pages > $this->page) {
			$this->plentySoapRequest->Page = $this->page;
			try {
				$response = $this->getPlentySoap()->GetItemsImages($this->plentySoapRequest);

				if ($response->Success == true) {

					$this->debug(__FUNCTION__ . ' Request Success - articles found : ' . count($response->ItemsImages->item) . ' / page : ' . $this->page);

					// auswerten
					$this->responseInterpretation($response);
				}

				$this->page++;
				lastUpdatePageUpdate(__CLASS__, $this->page);

			} catch (Exception $e) {
				$this->onExceptionAction($e);
			}
		}
	}

	private function storeToDB()
	{
		// insert itemsbase

		$countItemsImages = count($this->processedItemsImages);

		if ($countItemsImages > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countItemsImages items image records ...");

			DBQuery::getInstance()->insert('INSERT INTO `ItemsImages`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedItemsImages));
		}
	}
}
