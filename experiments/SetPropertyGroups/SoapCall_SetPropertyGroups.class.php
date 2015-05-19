<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_SetPropertyGroups extends PlentySoapCall
{

	public function execute()
	{
		try
		{
			$dbResult = DBQuery::getInstance()->select('SELECT BackendName, Description, FrontendName, IsMarkupPercental, Lang, PropertyGroupID, PropertyGroupTyp FROM SetPropertyGroups');



		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
}