<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemBundles.class.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'includes/SKUHelper.php';

class SoapCall_GetItemBundles extends PlentySoapCall {
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
	 * @var int
	 */
	private $countBundles = 0;

	/**
	 * @var PlentySoapRequest_GetItemBundles
	 */
	private $plentySoapRequest = null;

	/**
	 * @var array
	 */
	private $processedBundleItems;

	public function __construct() {
		parent::__construct(__CLASS__);
		$this->processedBundleItems = array();
	}

	public function execute() {
		list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart(__CLASS__);
		$lastUpdate = 0;

		if ($this->pages == -1) {
			try {
				$this->plentySoapRequest = Request_GetItemBundles::getRequest($lastUpdate, $this->startAtPage);

				if ($this->startAtPage > 0) {
					$this->debug(__FUNCTION__ . " Starting at page " . $this->startAtPage);
				}

				/*
				 * do soap call
				 */
				$response = $this->getPlentySoap()
					->GetItemBundles($this->plentySoapRequest);

				if (($response->Success == true) && isset($response->ItemBundles)) {
					// request successful, processing data..

					$recordsFound = count($response->ItemBundles->item);
					$pagesFound = $response->Pages;

					$this->debug(__FUNCTION__ . ' Request Success - records found : ' . $recordsFound . ' / pages : ' . $pagesFound);

					// process response
					$this->responseInterpretation($response);

					if ($pagesFound > $this->page) {
						$this->page = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						lastUpdatePageUpdate(__CLASS__, $this->page);
						$this->executePages();
					}
				} else {
					if (($response->Success == true) && !isset($response->ItemBundles)) {
						// request successful, but no data to process
						$this->debug(__FUNCTION__ . ' Request Success -  but no matching articles found');
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
	 * @param PlentySoapResponse_GetItemBundles $response
	 */
	private function responseInterpretation($response) {
		if (is_array($response->ItemBundles->item)) {
			foreach ($response->ItemBundles->item as $bundle) {
				$this->processBundle($bundle);
			}
		} else {
			$this->processBundle($response->ItemBundles->item);
		}
	}

	private function executePages() {
		while ($this->pages > $this->page) {
			$this->plentySoapRequest->Page = $this->page;
			try {
				$response = $this->getPlentySoap()
					->GetItemBundles($this->plentySoapRequest);

				if ($response->Success == true) {
					/** @noinspection PhpParamsInspection */
					$articlesFound = count($response->ItemsBase->item);
					$this->debug(__FUNCTION__ . ' Request Success - articles found : ' . $articlesFound . ' / page : ' . $this->page);

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

	private function storeToDB() {
		$countBundleItems = count($this->processedBundleItems);

		if ($this->countBundles > 0 && $countBundleItems > 0) {
			$this->debug(__FUNCTION__ . " storing $countBundleItems bundle items for " . $this->countBundles . " bundles");
			DBQuery::getInstance()
				->insert('INSERT INTO ItemBundles' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->processedBundleItems));
		}
	}

	/**
	 * @param PlentySoapObject_Bundle $bundle
	 */
	private function processBundle($bundle) {
		list($itemID, ,) = SKU2Values($bundle->SKU);

		if (is_array($bundle->Items->item)) {
			foreach ($bundle->Items->item as $bundleItem) {
				$this->processBundleItem($itemID, $bundleItem);
			}
		} else {
			$this->processBundleItem($itemID, $bundle->Items->item);
		}
		$this->countBundles++;
	}

	/**
	 * @param int                         $itemID
	 * @param PlentySoapObject_BundleItem $bundleItem
	 */
	private function processBundleItem($itemID, $bundleItem) {
		list($bundleItemItemID, ,) = SKU2Values($bundleItem->SKU);
		$this->processedBundleItems[] = array(
			'ItemID'             => $itemID,
			'BundleItemItemID'   => $bundleItemItemID,
			'BundleItemQuantity' => $bundleItem->Quantity,
		);
	}
}
