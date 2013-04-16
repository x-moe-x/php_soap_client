<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsBase.class.php';



class SoapCall_GetItemsBase extends PlentySoapCall
{
	private $page								=	0;
	private $pages								=	-1;
	private $oPlentySoapRequest_GetItemsBase	=	null;

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}

	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__);

		if ($this->pages == -1)
		{
			try
			{

				$oRequest_GetItemsBase = new Request_GetItemsBase();

				$this->oPlentySoapRequest_GetItemsBase = $oRequest_GetItemsBase->getRequest();

				/*
				 * do soap call
				*/
				$response		=	$this->getPlentySoap()->GetItemsBase($this->oPlentySoapRequest_GetItemsBase);

				if( $response->Success == true )
				{
					$articlesFound		= 	count($response->ItemsBase->item);
					$pagesFound			=	$response->Pages;

					$this->getLogger()->debug(__FUNCTION__.' Request Success - articles found : '.$articlesFound .' / pages : '.$pagesFound );

					// process response
					$this->responseInterpretation($response);

					if ( $pagesFound > $this->page )
					{
						$this->page		=	1;
						$this->pages	=	$pagesFound;

						$this->executePages();

					}
				}
				else
				{
					$this->getLogger()->debug(__FUNCTION__.' Request Error');
				}
			}
			catch(Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
		else
		{
			$this->executePages();
		}
	}

	private function responseInterpretation($oPlentySoapResponse_GetItemsBase)
	{
		if( is_array( $oPlentySoapResponse_GetItemsBase->ItemsBase->item ) )
		{
			foreach( $oPlentySoapResponse_GetItemsBase->ItemsBase->item AS $itemsBase)
			{
				$this->processItemsBase($itemsBase);
			}
		}
		else
		{
			$this->processItemsBase($oPlentySoapResponse_GetItemsBase->Orders->item);
		}
		$this->getLogger()->debug(__FUNCTION__.' : done' );
	}

	private function processItemsBase($oItemsBase)
	{
		$this->getLogger()->debug(__FUNCTION__.' : '
				. 	' ItemID : '			.$oItemsBase->ItemID		.','
				. 	' ItemNo : '			.$oItemsBase->ItemNo		.','
				. 	' Name : '				.$oItemsBase->Texts->Name
		);
	}

	private function executePages()
	{
		while ( $this->pages > $this->page )
		{
			$this->oPlentySoapRequest_GetItemsBase->Page = $this->page;
			try
			{
				$response		=	$this->getPlentySoap()->GetItemsBase( $this->oPlentySoapRequest_GetItemsBase );

				if( $response->Success == true )
				{
					$articlesFound	=	count($response->ItemsBase->item);
					$this->getLogger()->debug(__FUNCTION__.' Request Success - articles found : '.$articlesFound .' / page : '.$this->page );

					// auswerten
					$this->responseInterpretation( $response);
				}

				$this->page++;

			}
			catch(Exception $e)
			{
				$this->onExceptionAction($e);
			}

			// TODO remove after debugging:
			// stop after 3 pages
			if ($this->page >= 3)
				break;

		}
	}
}

?>