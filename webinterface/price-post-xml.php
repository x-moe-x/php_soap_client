<?php
require_once 'includes/basic_forward.inc.php';
require_once 'includes/smarty.inc.php';
require_once ROOT . 'api/ApiAmazon.class.php';

$page = isset($_POST['page']) ? $_POST['page'] : 1;
$rp = isset($_POST['rp']) ? $_POST['rp'] : 10;
$sortname = isset($_POST['sortname']) && !empty($_POST['sortname']) ? $_POST['sortname'] : 'ItemID';
$sortorder = isset($_POST['sortorder']) && !empty($_POST['sortorder']) ? $_POST['sortorder'] : 'ASC';
$query = isset($_POST['query']) ? $_POST['query'] : false;
$qtype = isset($_POST['qtype']) ? $_POST['qtype'] : false;
$filter_marking1ID = (isset($_POST['filterMarking1D']) && $_POST['filterMarking1D'] != '') ? explode(',', $_POST['filterMarking1D']) : null;
$filter_items = null;
$filter_itemNumbers = null;
$filter_itemNames = null;

switch ($sortname) {
	case 'ItemID' :
		break;
	case 'ItemNo' :
		break;
	case 'Marking1ID' :
		break;
	case 'ItemName' :
		$sortname = 'Name';
		break;
	case 'TimeData' :
		$sortname = 'WrittenTimeStamp';
		break;
	/*case 'ChangePrice' :
		$sortname = 'NewPrice';
		break;
	case 'TargetMarge' :
		$sortname = 'NewPrice';
		break;*/
	default :
		throw new RuntimeException("Unknown sort name: $sortname");
}

if ($query && $qtype) {
	switch ($qtype) {
		case 'ItemID' :
			if (preg_match('/(?:\\d+,)*\\d+/', trim($query))) {
				$filter_items = explode(',', $query);
			} else {
				$filter_items = -1;
			}
			break;
		case 'ItemNo' :
			if (preg_match('/(?:\\d+,)*\\d+/', trim($query))) {
				$filter_itemNumbers = explode(',', $query);
			} else {
				$filter_itemNumbers = -1;
			}
			break;
		case 'ItemName' :
			$filter_itemNames = explode(',', $query);
			break;
		default :
			throw new RuntimeException("Invalid query type: $qtype");
	}
}

header('Content-type: text/xml');
$smarty -> assign('data', ApiAmazon::getAmazonPriceData($page, $rp, $sortname, $sortorder, $filter_items, $filter_itemNumbers, $filter_itemNames, $filter_marking1ID));
$smarty -> display('amazon-post.tpl');
?>
