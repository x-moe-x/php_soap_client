<?php
require_once ROOT.'lib/log/Logger.class.php';

/**
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentymarketsSoapModelGenerator
{
	/**
	 * 
	 * @var SoapClient
	 */
	private $soapClient = null;
	
	/**
	 * 
	 * @var Logger
	 */
	private $logger = null;
	
	public function __construct()
	{
		$this->logger = Logger::instance(__CLASS__);
		
		if(!defined('WSDL_URL'))
		{
			$this->logger->crit('constant WSDL_URL is not set. Check config/soap.inc.php');
			exit;
		}
		
		if(!defined('SOAP_OUTPUT_BASE_DIR'))
		{
			$this->logger->crit('constant SOAP_OUTPUT_BASE_DIR is not set. Check config/soap.inc.php');
			exit;
		}
		
		if(!defined('SOAP_MODEL_DIR'))
		{
			$this->logger->crit('constant SOAP_MODEL_DIR is not set. Check config/soap.inc.php');
			exit;
		}
		
		if(!is_dir(SOAP_OUTPUT_BASE_DIR.'/'.SOAP_MODEL_DIR))
		{
			mkdir(SOAP_OUTPUT_BASE_DIR.'/'.SOAP_MODEL_DIR, 0755, true);
		}
		
		$this->soapClient = new SoapClient(WSDL_URL);
	}
	
	/**
	 * generate model files
	 */
	public function run()
	{
		foreach($this->soapClient->__getTypes() as $t)
		{
			$typeContent = $this->getTypeContent($t);
			
			if( is_array($typeContent) && isset($typeContent['content']) )
			{
				$file = SOAP_OUTPUT_BASE_DIR.'/'.SOAP_MODEL_DIR.'/'.$typeContent['class'].'.class.php';
				
				if(file_put_contents(	$file, 
									$typeContent['content'])===false)
				{
					$this->logger->err('can not write file ' . $file . ' check if permissions are correct.');
				}
				else
				{
					$this->logger->debug('write new model file: ' . $file);
				}
			}
		}
	}
	
	/**
	 * 
	 * @param string $typeString
	 * @return array
	 */
	private function getTypeContent($typeString)
	{
		preg_match('/(struct (.*?) {(.*)})/s',$typeString,$result);
		
		if(isset($result[2]) && isset($result[3]))
		{
			return array(
						'class'		=> SOAP_CLASS_PREFIX . $result[2],
						'content'	=> '<?php' . chr(10) . chr(10)
										.	'// generated ' . date('r') . chr(10) . chr(10)
										.	'/**' . chr(10)
										.	' * this is auto generated code, so do not change anything' . chr(10)
										.	' *' . chr(10)
										.	' */' . chr(10)
										.	'class ' . SOAP_CLASS_PREFIX . $result[2] . chr(10)
										.	'{' . chr(10)
										.	implode(chr(10), $this->getStructContent($result[3])) . chr(10)
										.	'}' . chr(10)
										.	'?>'
					);
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * 
	 * @param string $string
	 * @return array
	 */
	private function getStructContent($string)
	{
		$fields = explode(';',$string);
		
		foreach(array_keys($fields) as $i) 
		{
			$typeAndFieldname = explode(' ',trim($fields[$i]));
			if (count($typeAndFieldname) != 2)
			{
				unset($fields[$i]);
				continue;
			}
		
			list($type, $fieldname) = $typeAndFieldname; 
			
			
			$fields[$i] = '';
			$fields[$i] .= '	/**' . chr(10);
			$fields[$i] .= '	 *' . chr(10);
			$fields[$i] .= '	 * @var '.(strstr($type,'Type')
      										? SOAP_CLASS_PREFIX.$type
      										: $type) . chr(10);
			$fields[$i] .= '	 */' . chr(10);
			$fields[$i] .= '	public $'.$fieldname.';' . chr(10);
		}
		
		return $fields;
	}
}

?>
