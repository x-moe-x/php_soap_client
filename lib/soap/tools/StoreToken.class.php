<?php

require_once ROOT.'lib/log/Logger.class.php';
require_once ROOT.'lib/db/DBQuery.class.php';

/**
 * An extremely simple token storage tool.
 * 
 * Set SAVE_TOKEN_IN_DB to true, if you want to store your tokens in an datatable.
 * If not, your tokens are stored in an file.
 * 
 * The idea of the file storage was building a very simple solution, 
 * without the need for a database, so that the solution can be really 
 * understood and applied by anyone.
 * 
 * So do not wonder - just be happy :)
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class StoreToken
{
	/**
	 * 
	 * @var boolean
	 */
	const SAVE_TOKEN_IN_DB = true;
	
	/**
	 *
	 * @var StoreToken
	 */
	private static $instance = null;
	
	/**
	 * 
	 * @var string
	 */
	private $dest = ''; 
	
	private function __construct()
	{
		$this->dest = ROOT.'config/token.inc.php';
		if(!is_file($this->dest))
		{
			if(is_dir(dirname($this->dest)))
			{
				mkdir(dirname($this->dest), 0755, true);
			}
		}
	}
	
	/**
	 * singleton pattern
	 *
	 * @return StoreToken
	 */
	public static function getInstance()
	{
		if( !isset(self::$instance) || !(self::$instance instanceof StoreToken))
		{
			self::$instance = new StoreToken();
		}
	
		return self::$instance;
	}
	
	/**
	 * save new token to db or file
	 * 
	 * @param string $soapUser
	 * @param string $soapToken
	 * @param string $soapUserId
	 */
	public function saveToken($soapUser, $soapToken, $soapUserId)
	{
		if(self::SAVE_TOKEN_IN_DB===true)
		{
			$query = 'REPLACE INTO `plenty_soap_token` '.DBUtils::buildInsert(	array(
																						'soap_token_user' => $soapUser,
																						'soap_token_inserted' => 'NOW()',
																						'soap_token' => $soapToken,
																						'soap_token_user_id' => $soapUserId,
																						), 
																				array('soap_token_inserted'));
			$this->getLogger()->debug(__FUNCTION__.' save new token '.$query);
			
			DBQuery::getInstance()->replace($query);
		}
		else 
		{
			$token = array();
			
			if(is_file($this->dest))
			{
				require $this->dest;
			}
			
			$token[$soapUser] = array(
					'inserted' 	=> 	strtotime('today'),
					'usertoken' => 	$soapToken,
					'userId'	=> 	$soapUserId
			);
			
			$content = '<?php' 													. chr(10) . chr(10);
			$content .= '// generated ' . date('r') 							. chr(10) . chr(10);
			$content .= '$token = ' . var_export($token, true) . ';'	. chr(10);
			$content .= '?>'													. chr(10);
			
			file_put_contents($this->dest, $content);
		}
	}
	
	/**
	 * load existing token from db or file
	 * 
	 * @param string $soapUser
	 * @return array
	 */
	public function loadToken($soapUser)
	{
		if(self::SAVE_TOKEN_IN_DB===true)
		{
			$query = 'SELECT `soap_token_user_id`, `soap_token` 
						FROM `plenty_soap_token`
						WHERE `soap_token_user`="'.$soapUser.'" && DAY(`soap_token_inserted`)=DAY(NOW())';
			
			//$this->getLogger()->debug(__FUNCTION__.' '.$query);
			
			$result = DBQuery::getInstance()->selectAssoc($query);
			
			if(isset($result['soap_token_user_id']))
			{
				return array($result['soap_token_user_id'], $result['soap_token']);	
			}
		}
		else
		{
			$token = array();
			
			if(is_file($this->dest))
			{
				require $this->dest;
				if(isset($token[$soapUser]) && $token[$soapUser]['inserted'] == strtotime('today')) // tokens are valid from 00:00 to 24:00
				{
					return array($token[$soapUser]['userId'], $token[$soapUser]['usertoken']);
				}
			}				
		}
		
		return array();
	}
	
	/**
	 *
	 * @return Logger
	 */
	private function getLogger()
	{
		return Logger::instance(__CLASS__);
	}
}

?>
