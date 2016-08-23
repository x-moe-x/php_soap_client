<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetContentPages.class.php';


/**
 * Class SoapCall_SetContentPages
 */
class SoapCall_SetContentPages extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_CONTENT_PAGES_PER_CALL = 10;

	/**
	 * SoapCall_SetContentPages constructor.
	 * @return SoapCall_SetContentPages
	 */
	public function __construct() { parent::__construct(__CLASS__); }

	/**
	 *
	 */
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__ . ' writing content pages ...');
		try
		{
			// 1. get all content pages
			$contentPageDbResult = DBQuery::getInstance()->select($this->getQuery());

			// 2. for every XXX updates ...
			for ($page = 0, $maxPage = ceil($contentPageDbResult->getNumRows() / self::MAX_CONTENT_PAGES_PER_CALL); $page < $maxPage; $page++)
			{
				// ... prepare a separate request ...
				$request = new RequestContainer_SetContentPages(self::MAX_CONTENT_PAGES_PER_CALL);

				// ... fill in data
				$aWrittenUpdates = array();
				while (!$request->isFull() && ($aUnwrittenUpdate = $contentPageDbResult->fetchAssoc()))
				{
					$request->add($aUnwrittenUpdate);

					$aWrittenUpdates[] = array(
						'ContentPageID' => $aUnwrittenUpdate['ContentPageID'],
						'Lang'          => $aUnwrittenUpdate['Lang'],
						'WebstoreID'    => $aUnwrittenUpdate['WebstoreID'],
					);
				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->SetContentPages($request->getRequest());

				// 4. if successful ...
				if ($response->Success == true)
				{
					// ... then delete specified elements from SetContentPages

					DBQuery::getInstance()->delete('DELETE FROM SetContentPages WHERE (ContentPageID, Lang, WebstoreID) IN (' . implode(',', array_map(function ($element)
						{
							return '(' . $element['ContentPageID'] . ',"' . $element['Lang'] . '",' . $element['WebstoreID'] . ')';
						}, $aWrittenUpdates)) . ')');
					$this->debug(__FUNCTION__ . ' Successfully written page ' . $page . ' of ' . ($maxPage - 1));
				} else
				{
					// ... otherwise log error and try next request
					$this->debug(__FUNCTION__ . ' Request Error');
					die(print_r($request, true) . "\n" . print_r($response, true));
				}
			}
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @return string
	 */
	private function getQuery() { return "SELECT * FROM SetContentPages"; }

}