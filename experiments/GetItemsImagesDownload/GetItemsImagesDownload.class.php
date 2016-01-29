<?php
require_once realpath(dirname(__FILE__) . '/../../') . '/config/basic.inc.php';
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';

/**
 * Class GetItemsImagesDownload
 */
class GetItemsImagesDownload extends NX_Executable
{
	/**
	 * @return GetItemsImagesDownload
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
		// TODO: Implement execute() method.
	}
}
