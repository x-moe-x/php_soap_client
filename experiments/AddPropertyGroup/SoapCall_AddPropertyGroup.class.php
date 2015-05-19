<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_AddPropertyGroup extends PlentySoapCall
{

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