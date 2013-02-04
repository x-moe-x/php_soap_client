<?php
/**
 * 
 * @param string $classname
 * @return boolean
 */
function soapModelLoader($classname)
{
	$file = ROOT.'lib/soap/model/'.$classname.'.class.php';
	
	if(is_file($file))
	{
		require_once $file;
		return true;
	}

	return false;
}



spl_autoload_register('soapModelLoader');

?>