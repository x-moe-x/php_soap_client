<?php
require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * Class SoapCall_GetWarehouseList
 */
class SoapCall_GetWarehouseList extends PlentySoapCall
{

	/**
	 * @var array
	 */
	private $aWarehouseData;

	/**
	 * @return SoapCall_GetWarehouseList
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->aWarehouseData = array();

		DBQuery::getInstance()->truncate('TRUNCATE TABLE `WarehouseList`');
	}

	/**
	 * @return void
	 */
	public function execute()
	{
		try
		{
			$oPlentySoapResponse_GetWarehouseList = $this->getPlentySoap()->GetWarehouseList(new PlentySoapRequest_GetWarehouseList());

			if ($oPlentySoapResponse_GetWarehouseList->Success == true)
			{
				// process response
				$this->responseInterpretation($oPlentySoapResponse_GetWarehouseList);

				$this->storeToDB();
			} else
			{
				$this->getLogger()->debug(__FUNCTION__ . ' Request Error');
			}
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * @param PlentySoapResponse_GetWarehouseList $oPlentySoapResponse_GetWarehouseList
	 *
	 * @return void
	 */
	private function responseInterpretation(PlentySoapResponse_GetWarehouseList $oPlentySoapResponse_GetWarehouseList)
	{
		if (is_array($oPlentySoapResponse_GetWarehouseList->WarehouseList->item))
		{
			foreach ($oPlentySoapResponse_GetWarehouseList->WarehouseList->item as $oPlentySoapObject_GetWarehouseList)
			{
				$this->processWarehouse($oPlentySoapObject_GetWarehouseList);
			}
		} else
		{
			$this->processWarehouse($oPlentySoapResponse_GetWarehouseList->WarehouseList->item);
		}
	}

	/**
	 * @param PlentySoapObject_GetWarehouseList $oPlentySoapObject_GetWarehouseList
	 *
	 * @return void
	 */
	private function processWarehouse($oPlentySoapObject_GetWarehouseList)
	{
		$this->aWarehouseData[] = array(
			'WarehouseID' => $oPlentySoapObject_GetWarehouseList->WarehouseID,
			'Name'        => $oPlentySoapObject_GetWarehouseList->Name,
			'Type'        => $oPlentySoapObject_GetWarehouseList->Type
		);
	}

	/**
	 * @return void
	 */
	private function storeToDB()
	{
		$dataCount = count($this->aWarehouseData);

		if ($dataCount > 0)
		{
			$this->getLogger()->debug(__FUNCTION__ . " storing $dataCount warehouse records to db");
			DBQuery::getInstance()->insert('INSERT INTO `WarehouseList`' . DBUtils::buildMultipleInsert($this->aWarehouseData));
		}
	}
}
