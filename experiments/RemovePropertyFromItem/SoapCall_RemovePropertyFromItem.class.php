<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * Class SoapCall_RemovePropertyFromItem
 */
class SoapCall_RemovePropertyFromItem extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_PROPERTIES_FROM_ITEMS_PER_PAGE = 100;

	/**
	 * @return SoapCall_RemovePropertyFromItem
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	/**
	 *
	 */
	public function execute()
	{
		$this->debug(__FUNCTION__ . ' remove properties to items...');
		try
		{
			//TODO remove after debugging
			$this->debug(__FUNCTION__ . ' NOT YET IMPLEMENTED');
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}
