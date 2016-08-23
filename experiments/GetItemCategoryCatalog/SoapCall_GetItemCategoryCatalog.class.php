<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Enables execution of GetContentPage SOAP-Call. Retrieves all content pages available in a specific language
 */
class SoapCall_GetItemCategoryCatalog extends PlentySoapCall
{

	/**
	 * @return SoapCall_GetItemCategoryCatalog
	 */
	public function __construct() { parent::__construct(__CLASS__); }

	/**
	 * overrides PlenySoapCall's execute method
	 *
	 * @return void
	 */
	public function execute()
	{
		try
		{
			// implement call
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}

