<?php
require_once ROOT.'lib/log/Logger.class.php';

/**
 * 
 * @author phileon
 * @copyright plentymarkets GmbH www.plentymarkets.com
 */
class PlentymarketsSoapControllerGenerator
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
	
	/**
	 * 
	 * @var string
	 */
	private $saveDir = '';
	
	/**
	 * List of soap functions
	 * 
	 * @var array
	 */
	private $classMap = array();
	
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
	
		if(!defined('SOAP_CONTROLLER_DIR'))
		{
			$this->logger->crit('constant SOAP_CONTROLLER_DIR is not set. Check config/soap.inc.php');
			exit;
		}
		
		$this->saveDir = SOAP_OUTPUT_BASE_DIR.'/'.SOAP_CONTROLLER_DIR.'/';
		
		if(!is_dir($this->saveDir))
		{
			mkdir($this->saveDir, 0755, true);
		}
	
		$this->soapClient = new SoapClient(WSDL_URL);
	}
	
	public function run()
	{
		$this->writeController();
	}
	
	private function writeController()
	{
		$functionList = $this->getControllerFunctions();
		
		$content = '';
		
		$content .= '<?php' . chr(10) . chr(10);
		$content .= '// generated ' . date('r') . chr(10) . chr(10);
		$content .= '' . chr(10);
		$content .= '/**' . chr(10);
		$content .= ' * plentymarkets SOAP-API controller class' . chr(10);
		$content .= ' *' . chr(10);
		$content .= ' * this is auto generated code, so do not change anything' . chr(10);
		$content .= ' *' . chr(10);
		$content .= ' * @class ' . SOAP_CONTROLLER_CLASS_NAME . chr(10);
		$content .= ' */' . chr(10);
		$content .= 'class ' . SOAP_CONTROLLER_CLASS_NAME . ' extends SoapClient' . chr(10);
		$content .= '{' . chr(10);
		$content .= '	/**' . chr(10);
		$content .= '	 *' . chr(10);
		$content .= '	 * @var LoginInformation' . chr(10);
		$content .= '	 */' . chr(10);
		$content .= '	private $LoginInformation;' . chr(10);
		$content .= '	' . chr(10);
		$content .= '	public function __construct($wsdl="", $options=array())' . chr(10);
		$content .= '	{' . chr(10);
		$content .= '		if (!strlen($wsdl))' . chr(10);
		$content .= '		{' . chr(10);
		$content .= '			$wsdl = "'.WSDL_URL.'";' . chr(10);
		$content .= '		}' . chr(10);
		$content .= '		' . chr(10);
		$content .= '		$options["features"] = SOAP_SINGLE_ELEMENT_ARRAYS;' . chr(10);
		$content .= '		$options["version"] = SOAP_1_2;' . chr(10);
		$content .= '		$options["classmap"] = '.str_replace(chr(10), chr(10).chr(9).chr(9).chr(9).chr(9), var_export($this->classMap, true)).';' . chr(10);
		$content .= '		parent::__construct($wsdl, $options);' . chr(10);
		$content .= '	}' . chr(10);
		$content .= '	' . chr(10);
		
		$content .= $functionList . chr(10);

		$content .= '}' . chr(10);
		
		$this->writeFile(SOAP_CONTROLLER_CLASS_NAME, $content);
	}
	
	/**
	 * 
	 * @return string
	 */
	private function getControllerFunctions()
	{
		$result = '';
		
		foreach ($this->soapClient->__getFunctions() as $f) 
		{
			$result .= $this->getFunction($f);
		}
		
		return $result;
	}
	
	/**
	 * 
	 * @param string $string
	 * @return string
	 */
	private function getFunction($string)
	{
		$result = '';
		
		preg_match('/(.*) (\w+)\((.*)\)/',$string,$res);
		// if there's a function according to the above match... (res[1])
		if(isset($res[1]))
		{
			// ... generate returntypes and requirements from res[1]
			list($returntypes, $requires) = $this->getReturn($res[1]);
			
			//TODO add return types to classmap
			
			// ... array from res[3] which contains elements of '<arg type> <arg>'
			$argumentStrings = $this->getArguments($res[3]);
			
			$withLogin = false;
			
			// ... get first arg type and first arg to use for a check of LoginInformation type
			list ($firstType, $firstArg) = explode(" ", trim($argumentStrings[0]));
			
			if ($firstType == 'LoginInformation') 
			{
				$withLogin = true;
			}
			
			// ... for each element of $argumentStrings perform ...
			foreach (array_keys($argumentStrings) as $i)
			{
				// ... get current arument type
				list($currentArgumentType) = explode(" ",trim($argumentStrings[$i]));
				
				if(isset($currentArgumentType) && strlen($currentArgumentType))
				{
					// and store it into the class map for further use
					$this->classMap[$currentArgumentType] = SOAP_CLASS_PREFIX . trim($currentArgumentType);
				}
				
				$argumentStrings[$i] = SOAP_CLASS_PREFIX . trim($argumentStrings[$i]);
			}
			
			// Response
			$this->classMap[$returntypes] = SOAP_CLASS_PREFIX . trim($returntypes);
			
			$result .= '	/**' . chr(10);
			$result .= '	 *' . chr(10);
			$result .= '	 * Soap function call' . chr(10);
			$result .= '	 * ' . $string . chr(10);
			$result .= '	 *' . chr(10);
			
			foreach($argumentStrings as $a) 
			{
				$result .= '	 * @param ' . $a . chr(10);
			}
			
			$result .= '	 *' . chr(10);
			$result .= '	 * @return ' . $returntypes . chr(10);
			$result .= '	 */' . chr(10);
			$result .= '	public function ' .$res[2] . '('.$this->implodeArguments($argumentStrings, $withLogin). ')'. chr(10);
			$result .= '	{' . chr(10);
			$result .= '		' . $requires . chr(10);
			$result .= '		return parent::__soapCall("'.$res[2].'",array('.implode(", ",$this->getVars2Arguments($argumentStrings, $withLogin)).'));' . chr(10);
			$result .= '	}' . chr(10);
			$result .= '	' . chr(10);
		}
		
		return $result;
	}
	
	/**
	 * 
	 * @param array $args
	 * @param boolean $withLogin
	 * @return string
	 */
	private function implodeArguments($args, $withLogin) 
	{
		if ($withLogin===true)
		{
			unset($args[0]);
		}	
		
		return implode(", ",$args);
	}
	
	/**
	 * 
	 * @param array $args
	 * @param boolean $withLogin
	 * @return args
	 */
	private function getVars2Arguments($args, $withLogin) 
	{
		foreach(array_keys($args) as $a) 
		{
			list($type, $args[$a]) = explode(" ",trim($args[$a]));
		}
		
		if ($withLogin===true) 
		{
			$args[0] = '$this->LoginInformation';
		}
		
		return $args;
	}
	
	/**
	 * 
	 * @param string $string
	 * @return array
	 */
	private function getReturn($string) 
	{
		if (!preg_match('#list\((.*)\)#',$string,$save)) 
		{
			list($arg0) = explode(" ",$string);
			
			return array($string, 'require_once("'.SOAP_OUTPUT_BASE_DIR.'/'.SOAP_MODEL_DIR.'/'.SOAP_CLASS_PREFIX . trim($arg0).'.class.php");');
		}
	
		$args = explode(",", $save[1]);
	
		$c = array();
		
		foreach (array_keys($args) as $i) 
		{
			list($arg0) = explode(" ",$args[$i]);
			
			if (strlen($arg0) > 3) 
			{
				$this->classMap[$arg0] = SOAP_CLASS_PREFIX . trim($arg0);
				
				$c[] = 'require_once("'.SOAP_OUTPUT_BASE_DIR.'/'.SOAP_MODEL_DIR.'/'.SOAP_CLASS_PREFIX . trim($arg0).'.class.php");';
			}
			
			$args[$i] = SOAP_CLASS_PREFIX . trim($args[$i]);
		}
	
		return array('list('.implode(", ",$args).')', implode("\n\t\t",$c));
	}
	
	/**
	 * 
	 * @param string $string
	 * @return array
	 */
	private function getArguments($string) 
	{
		$list = explode(",",$string);
		
		foreach(array_keys($list) as $i) 
		{
			trim($list[$i]);
		}
		
		return $list;
	}
	
	/**
	 * 
	 * @param string $className
	 * @param string $content
	 */
	private function writeFile($className, $content)
	{
		$file = $this->saveDir.$className.'.class.php';
		
		if(file_put_contents( $file, $content)===false)
		{
			$this->logger->err('can not write file ' . $file . ' check if permissions are correct.');
		}
		else
		{
			$this->logger->debug('write new controller file: ' . $file);
		}
	}
}

?>