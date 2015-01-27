<?php
ob_start();
require_once 'includes/basic_forward.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'api/ApiJansen.class.php';
ob_end_clean();

$smarty -> assign('data', ApiJansen::getJansenUnmatchedData());

header('Content-type: text/xml');
$smarty -> display('jansen_unmatched-post.tpl');
?>
