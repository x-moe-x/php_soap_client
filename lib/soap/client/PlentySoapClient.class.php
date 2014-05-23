<?php

require_once ROOT.'lib/log/Logger.class.php';
require_once ROOT.'config/soap.inc.php';

/*
 * save and load token and user
 */
require_once ROOT.'lib/soap/tools/StoreToken.class.php';

/*
 * generated soap controller
 */
require_once ROOT.'lib/soap/controller/PlentySoap.class.php';


/**
 *
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentySoapClient
{
	/**
	 * singleton instance
	 *
	 * @var PlentySoapClient
	 */
	private static $instance	=	null;

	/**
	 * @var PlentySoap
	 */
	private $soapController;


	private function __construct()
	{
		$this->initSoapController();
	}

	/**
	 * singleton pattern
	 *
	 * @return PlentySoapClient
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof PlentySoapClient) )
		{
			self::$instance = new PlentySoapClient();
		}

		return self::$instance;
	}

	/**
	 *
	 * @return Logger
	 */
	private function getLogger()
	{
		return Logger::instance(__CLASS__);
	}

	/**
	 *
	 * @param PlentySoap $soapController
	 */
	private function setSoapController(PlentySoap $soapController)
	{
		$this->soapController	=	$soapController;
	}

	/**
	 * @return PlentySoap
	 */
	public function getPlentySoap()
	{
		return $this->soapController;
	}

	/**
	 * load last soap token or get a new one
	 *
	 */
	private function initSoapController()
	{
		/*
		 * load UserId and Token
		 */
		list($savedUserId, $savedUserToken)	=	StoreToken::getInstance()->loadToken( SOAP_USER );

		/*
		 * no valid token, userId available
		 */
		if( !strlen( $savedUserToken )  || !strlen( $savedUserId ) )
		{
			$this->getLogger()->debug(__FUNCTION__.' : saved token and userId no longer valid');

			$this->doAuthentification();
		}
		else
		{
			$this->getLogger()->debug(__FUNCTION__.' : token and userId loaded');

			$this->setSoapHeader($savedUserId, $savedUserToken);
		}
	}

	/**
	 * create new soap controller
	 * create new soap header
	 * add header to controller
	 *
	 * @param String $userID
	 * @param String $userToken
	 */
	private function setSoapHeader($userID, $userToken)
	{
		$aHeader			=	array(
										'UserID' 	=>	$userID,
										'Token'		=> 	$userToken
									);

		$auth_vals    		= 	new SoapVar($aHeader, SOAP_ENC_OBJECT);
		$ns 				=	"Authentification";
		$oSoapHeader 		=	new SoapHeader($ns,'verifyingToken', $auth_vals, false);

		$oPlentySoap	 	=	 new PlentySoap( WSDL_URL );

		$oPlentySoap->__setSoapHeaders( $oSoapHeader );
		$this->setSoapController( $oPlentySoap );

		$this->getLogger()->debug(__FUNCTION__.' : header created / controller set');
	}

	/**
	 * run get GetAuthentificationToken and call setSoapHeader()
	 *
	 * @throws Exception
	 */
	private function doAuthentification()
	{
		/*
		 * request object
		 */
		$oPlentySoapRequest_GetAuthentificationToken			=	new PlentySoapRequest_GetAuthentificationToken();
		$oPlentySoapRequest_GetAuthentificationToken->Username	=	SOAP_USER;
		$oPlentySoapRequest_GetAuthentificationToken->Userpass	=	SOAP_PASSWORD;

		/*
		 * response object
		 */
		$oPlentySoapResponse_GetAuthentificationToken			=	new PlentySoapResponse_GetAuthentificationToken();

		try
		{
			$oPlentySoap	 							=	new PlentySoap( WSDL_URL );
			$oPlentySoapResponse_GetAuthentificationToken		=	$oPlentySoap->GetAuthentificationToken(
																							$oPlentySoapRequest_GetAuthentificationToken );
			if( $oPlentySoapResponse_GetAuthentificationToken->Success == true )
			{
				$userId	= $oPlentySoapResponse_GetAuthentificationToken->UserID;
				$token 	= $oPlentySoapResponse_GetAuthentificationToken->Token;

				/*
				 * save token and userId
				 */
				StoreToken::getInstance()->saveToken(SOAP_USER, $token, $userId);

				$this->setSoapHeader($userId, $token);
				return;
			}
			else
			{
				$messages	=	'';

				// error messages
				if (is_array($oPlentySoapResponse_GetAuthentificationToken->ResponseMessages->item))
				{
					foreach ($oPlentySoapResponse_GetAuthentificationToken->ResponseMessages->item as $Message)
					{
						if (is_array($Message->ErrorMessages->item))
						{
							foreach ($Message->ErrorMessages->item as $ErrorMessage)
							{
								$messages .= $ErrorMessage->Value . ': ' . $ErrorMessage->Key;
							}
						}
					}
				}

				$this->getLogger()->crit(__FUNCTION__.': error getting token: '.$messages );

				throw new Exception('error getting token');
			}
		}
		catch(Exception $e)
		{
			/*
			 * catch exception only for logging, than throw a new one
			 */
			$this->getLogger()->crit(__FUNCTION__.': exception getting token: '.$e->getMessage() );

			throw $e;
		}
	}

	public function updateToken()
	{
		$this->doAuthentification();
	}
}

?>
