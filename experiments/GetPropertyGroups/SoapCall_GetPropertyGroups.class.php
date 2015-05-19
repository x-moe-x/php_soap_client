<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';


class SoapCall_GetPropertyGroups extends PlentySoapCall
{
	/**
	 * @var int
	 */
	const MAX_LINKED_ITEMS_PER_PAGE = 250;

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		// TODO: Implement execute() method.
	}
}