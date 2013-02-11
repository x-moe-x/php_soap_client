<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

/**
 * This class retrieves all producer and can create new producer.
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentyItemDataPushProducer extends PlentySoapCall
{

	private $producerList = array();

	/**
	 *
	 * @var PlentyItemDataPushProducer
	 */
	private static $instance = null;
	
	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	/**
	 * singleton pattern
	 *
	 * @return PlentyItemDataPushProducer
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentyItemDataPushProducer))
		{
			self::$instance = new PlentyItemDataPushProducer();
		}
	
		return self::$instance;
	}
	
	public function execute()
	{
		try
		{
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->GetProducers();
				
			/*
			 * check soap response
			*/
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' request succeed');
		
				/*
				 * parse and save the data
				 */
				$this->parseResponse($response);
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' request error');
			}
		}
		catch(Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}
	
	/**
	 * Parse the response
	 *
	 * @param PlentySoapResponse_PlentySoapObject_GetProducers $response
	 */
	private function parseResponse($response)
	{
		if(is_array($response->Producers->item))
		{
			foreach($response->Producers->item as $item)
			{
				$this->producerList[$item->ProducerName] = $item->ProducerID;
			}
		}
		/*
		 * only one country of delivery
		 */
		elseif (is_object($response->Producers->item))
		{
			$this->producerList[$response->Producers->item->ProducerName] = $response->Producers->item->ProducerID;
		}
	}
	
	/**
	 * 
	 * @param string $name
	 * @return number
	 */
	public function checkProducer($name)
	{
		if(isset($this->producerList[$name]))
		{
			return $this->producerList[$name];
		}
		
		return 0;
	}
	
	/**
	 * 
	 * @param string $name
	 */
	public function saveNewProducer($name)
	{
		try
		{
			$oPlentySoapObject_Producer = new PlentySoapObject_Producer();
			$oPlentySoapObject_Producer->ProducerName = $name;
			$oPlentySoapObject_Producer->ProducerExternalName = $name;
			
			$oPlentySoapRequest_SetProducers = new PlentySoapRequest_SetProducers();
			$oPlentySoapRequest_SetProducers->Producers[] = $oPlentySoapObject_Producer;
			
			/*
			 * do soap call
			 */
			$response	=	$this->getPlentySoap()->SetProducers($oPlentySoapRequest_SetProducers);

			/*
			 * check soap response
			 */
			if( $response->Success == true )
			{
				$this->getLogger()->debug(__FUNCTION__.' request succeed');
		
				/*
				 * parse and save the data
				 */
				if(is_array($response->SuccessMessages->item))
				{
					foreach($response->SuccessMessages->item as $item)
					{
						if($item->Code=='SBLANK')
						{
							/*
							 * everything seems to be fine so let's call the new producer list
							 * 
							 */
							$this->execute();
							
							return;
						}
					}
				}
			}
			else
			{
				$this->getLogger()->debug(__FUNCTION__.' request error');
			}
		}
		catch(Exception $e)
		{
			$this->getLogger()->crit(__FUNCTION__.' request error '. $e->getMessage());
		}
	}

}
?>