<?php
require_once ROOT . 'includes/RequestContainer.class.php';


class RequestContainer_SetProperties extends RequestContainer
{

	public function __construct($capacity)
	{
		parent::__construct($capacity);
	}

	/**
	 * returns the assembled request
	 *
	 * @return mixed
	 */
	public function getRequest()
	{
		// TODO: Implement getRequest() method.
	}
}