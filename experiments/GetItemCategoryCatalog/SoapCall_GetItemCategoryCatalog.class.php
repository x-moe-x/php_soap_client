<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBLastUpdate.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once 'Request_GetItemCategoryCatalog.class.php';

/**
 * Class SoapCall_GetItemCategoryCatalog
 */
class SoapCall_GetItemCategoryCatalog extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_CATEGORY_PAGES_PER_CALL = 250;

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
	 * @var PlentySoapRequest_GetItemCategoryCatalog
	 */
	private $plentySoapRequest = null;

	/**
	 * @var array
	 */
	private $categories;

	/**
	 * @return SoapCall_GetItemCategoryCatalog
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
		$this->categories = array();
	}

	/**
	 * overrides PlenySoapCall's execute method
	 *
	 * @return void
	 */
	public function execute()
	{
		list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart(__CLASS__);
		$lastUpdate = 0;
		$this->startAtPage = 0;

		if ($this->pages == -1)
		{
			try
			{
				$this->plentySoapRequest = Request_GetItemCategoryCatalog::getRequest($lastUpdate, $currentTime, $this->startAtPage);

				if ($this->startAtPage > 0)
				{
					$this->debug(__FUNCTION__ . " Starting at page " . $this->startAtPage);
				}

				/*
				 * do soap call
				 */
				$response = $this->getPlentySoap()->GetItemCategoryCatalog($this->plentySoapRequest);

				if (($response->Success == true) && isset($response->Categories))
				{
					// request successful, processing data..

					/** @noinspection PhpParamsInspection */
					$categoriesFound = count($response->Categories->item);
					$pagesFound = $response->Pages;

					$this->debug(__FUNCTION__ . ' Request Success - categories found : ' . $categoriesFound . ' / pages : ' . $pagesFound);

					// process response
					$this->responseInterpretation($response);

					if ($pagesFound > $this->page)
					{
						$this->page = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						lastUpdatePageUpdate(__CLASS__, $this->page);
						$this->executePages();

					}
				} else
				{
					if (($response->Success == true) && !isset($response->Categories))
					{
						// request successful, but no data to process
						$this->debug(__FUNCTION__ . ' Request Success -  but no matching categories found');
					} else
					{
						$this->debug(__FUNCTION__ . ' Request Error');
					}
				}
			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		} else
		{
			$this->executePages();
		}

		$this->storeToDB();
		lastUpdateFinish($currentTime, __CLASS__);
	}

	/**
	 * @param $response
	 */
	private function responseInterpretation($response)
	{
		if (is_array($response->Categories->item))
		{

			foreach ($response->Categories->item AS $category)
			{
				$this->processCategory($category);
			}
		} else
		{
			$this->processCategory($response->Categories->item);
		}
	}

	private function executePages()
	{
		while ($this->pages > $this->page)
		{
			$this->plentySoapRequest->Page = $this->page;
			try
			{
				$response = $this->getPlentySoap()->GetItemCategoryCatalog($this->plentySoapRequest);

				if ($response->Success == true)
				{
					/** @noinspection PhpParamsInspection */
					$categoriesFound = count($response->Categories->item);
					$this->debug(__FUNCTION__ . ' Request Success - categories found : ' . $categoriesFound . ' / page : ' . $this->page);

					// auswerten
					$this->responseInterpretation($response);
				}

				$this->page++;
				lastUpdatePageUpdate(__CLASS__, $this->page);

			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
	}

	private function storeToDB()
	{
		$countCategories = count($this->categories);
		if ($countCategories > 0)
		{
			$this->debug(__FUNCTION__ . ': storing ' . $countCategories . ' records of category data');
			DBQuery::getInstance()->insert('INSERT INTO GetItemCategoryCatalog' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->categories));
		}
	}

	private function processCategory($category)
	{
		$this->categories[] = array(
			'CategoryID'      => $category->CategoryID,
			'Image1'          => $category->Image1,
			'Image2'          => $category->Image2,
			'Lang'            => $category->Lang,
			'MetaDescription' => $category->MetaDescription,
			'MetaKeywords'    => $category->MetaKeywords,
			'MetaTitle'       => $category->MetaTitle,
			'Name'            => $category->Name,
			'NameURL'         => $category->NameURL,
			'Position'        => $category->Position,
			'Text'            => $category->Text,
		);
	}
}

