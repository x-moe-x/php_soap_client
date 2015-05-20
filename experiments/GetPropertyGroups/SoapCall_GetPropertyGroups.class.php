<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetPropertyGroups.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';


class SoapCall_GetPropertyGroups extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_LINKED_ITEMS_PER_PAGE = 250;

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
	 * @var PlentySoapRequest_GetPropertyGroups
	 */
	private $plentySoapRequest = null;

	/**
	 * @var array
	 */
	private $storePropertyGroups;

	public function __construct()
	{
		parent::__construct(__CLASS__);
		$this->storePropertyGroups = array();
	}

	public function execute()
	{
		//TODO remove after debugging
		//list($lastUpdate, $currentTime, $this->startAtPage) = lastUpdateStart(__CLASS__);

		if ($this->pages == -1)
		{
			try
			{
				$request = new Request_GetPropertyGroups(self::MAX_LINKED_ITEMS_PER_PAGE);
				//TODO remove after debugging
				//$this->plentySoapRequest = $request->getRequest($lastUpdate, $currentTime, $this->startAtPage);
				$this->plentySoapRequest = $request->getRequest(null, null, $this->startAtPage);

				if ($this->startAtPage > 0)
				{
					$this->debug(__FUNCTION__ . " Starting at page " . $this->startAtPage);
				}

				/*
				 * do soap call
				 */
				/** @var PlentySoapResponse_GetPropertyGroups $response */
				$response = $this->getPlentySoap()->GetPropertyGroups($this->plentySoapRequest);

				if (($response->Success == true) && isset($response->PropertyGroups))
				{
					// request successful, processing data..

					/** @noinspection PhpParamsInspection */
					$propertyGroupsFound = count($response->PropertyGroups->item);
					$pagesFound = $response->Pages;

					$this->debug(__FUNCTION__ . ' Request Success - property groups found : ' . $propertyGroupsFound . ' / pages : ' . $pagesFound);

					// process response
					$this->responseInterpretation($response);

					if ($pagesFound > $this->page)
					{
						$this->page = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						//TODO remove after debugging
						//lastUpdatePageUpdate(__CLASS__, $this->page);
						$this->executePages();

					}
				} else
				{
					if (($response->Success == true) && !isset($response->PropertyGroups))
					{
						// request successful, but no data to process
						$this->debug(__FUNCTION__ . ' Request Success -  but no matching property groups found');
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
		//TODO remove after debugging
		//lastUpdateFinish($currentTime, __CLASS__);
	}

	/**
	 * @param PlentySoapResponse_GetPropertyGroups $response
	 */
	private function responseInterpretation($response)
	{
		if (is_array($response->PropertyGroups->item))
		{

			foreach ($response->PropertyGroups->item AS $propertyGroup)
			{
				$this->processPropertyGroup($propertyGroup);
			}
		} else
		{
			$this->processPropertyGroup($response->PropertyGroups->item);
		}
	}

	/**
	 * @param PlentySoapObject_PropertyGroup $propertyGroup
	 */
	private function processPropertyGroup($propertyGroup)
	{
		$this->storePropertyGroups[] = array(
			'PropertyGroupID'   => $propertyGroup->PropertyGroupID,
			'BackendName'       => $propertyGroup->BackendName,
			'Lang'              => $propertyGroup->Lang,
			'PropertyGroupTyp'  => $propertyGroup->PropertyGroupTyp,
			'IsMarkupPercental' => $propertyGroup->IsMarkupPercental,
			'FrontendName'      => $propertyGroup->FrontendName,
			'Description'       => $propertyGroup->Description === '' ? null : $propertyGroup->Description,
		);
	}

	private function executePages()
	{
		while ($this->pages > $this->page)
		{
			$this->plentySoapRequest->Page = $this->page;
			try
			{
				$response = $this->getPlentySoap()->GetPropertyGroups($this->plentySoapRequest);

				if ($response->Success == true)
				{
					/** @noinspection PhpParamsInspection */
					$propertyGroupsFound = count($response->PropertyGroups->item);
					$this->debug(__FUNCTION__ . ' Request Success - property groups found : ' . $propertyGroupsFound . ' / page : ' . $this->page);

					// auswerten
					$this->responseInterpretation($response);
				}

				$this->page++;
				//TODO remove after debugging
				//lastUpdatePageUpdate(__CLASS__, $this->page);

			} catch (Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
	}

	private function storeToDB()
	{
		$countPropertyGroups = count($this->storePropertyGroups);

		if ($countPropertyGroups > 0)
		{
			$this->getLogger()->info(__FUNCTION__ . " : storing $countPropertyGroups property group records ...");
			DBQuery::getInstance()->truncate('TRUNCATE `PropertyGroups`');
			DBQuery::getInstance()->insert('INSERT INTO `PropertyGroups`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storePropertyGroups));
		}
	}
}