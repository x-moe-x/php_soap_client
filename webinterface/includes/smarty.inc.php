<?php

require_once 'basic_forward.inc.php';
require_once ROOT . 'webinterface/smarty/libs/Smarty.class.php';

$smarty = new Smarty();

$smarty -> setTemplateDir('smarty/templates');
$smarty -> setCompileDir('smarty/templates_c');
$smarty -> setCacheDir('smarty/cache');
$smarty -> setConfigDir('smarty/configs');

$smarty -> clearAllCache();
?>