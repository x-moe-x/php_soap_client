<?php
require_once 'includes/basic_forward.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'api/ApiAmazon.class.php';

$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) ? $_POST['sortname'] : 'ItemID';
$sortorder = isset($_POST['sortorder']) ? $_POST['sortorder'] : 'ASC';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;

header('Content-type: text/xml');
$smarty -> assign('data', ApiAmazon::getAmazonPriceData($page, $rp, $sortname, $sortorder));
$smarty -> display('amazon-post.tpl');
?>
