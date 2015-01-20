<?php
require_once 'includes/basic_forward.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'api/ApiJansen.class.php';

$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) && !empty($_POST['sortname']) ? $_POST['sortname'] : 'EAN';
$sortorder = isset($_POST['sortorder']) && !empty($_POST['sortorder']) ? $_POST['sortorder'] : 'ASC';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;
$filter_eans = null;
$filter_externalItemIDs = null;
$filter_itemIDs = null;
$filter_itemNames = null;

if ($query && $qtype) {
	switch ($qtype) {
		case 'EAN' :
			if (preg_match_all('/(\\d{13})/', $query, $matches)) {
				$filter_eans = $matches[0];
			} else {
				$filter_eans = -1;
			}
			break;
		case 'ExternalItemID' :
			if (preg_match_all('/(\\w+)/', $query, $matches)) {
				$filter_externalItemIDs = $matches[0];
			} else {
				$filter_externalItemIDs = -1;
			}
			break;
		case 'ItemID' :
			if (preg_match_all('/(\\d+)/', $query, $matches)) {
				$filter_itemIDs = $matches[0];
			} else {
				$filter_itemIDs = -1;
			}
			break;
		case 'Name' :
			if (preg_match_all('/(\\w+)/', $query, $matches)) {
				$filter_itemNames = $matches[0];
			} else {
				$filter_itemNames = -1;
			}
			break;
		default :
			throw new RuntimeException("Invalid query type: $qtype");
	}
}

header('Content-type: text/xml');
$smarty -> assign('data', ApiJansen::getJansenStockData($page, $rp, $sortname, $sortorder, $filter_eans, $filter_externalItemIDs, $filter_itemIDs, $filter_itemNames));
$smarty -> display('jansen_stock-post.tpl');
?>
