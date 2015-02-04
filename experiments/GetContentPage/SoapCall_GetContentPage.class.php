<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetContentPage.class.php';

/**
 * Enables execution of GetContentPage SOAP-Call. Retrieves all content pages available in a specific language
 */
class SoapCall_GetContentPage extends PlentySoapCall
{

	public function __construct()
	{
	}

	/**
	 * overrides PlenySoapCall's execute method
	 *
	 * @return void
	 */
	public function execute()
	{
		try
		{

		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}

?>
