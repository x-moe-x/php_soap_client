<?php
ob_start();

require_once 'includes/basic_forward.inc.php';
require_once ROOT . 'includes/GetConfig.php';

$result = array('Message' => null, 'Value' => null);

if (isset($_POST['key']) && isset($_POST['value'])) {
	// extract warehouse id & date

	// if warehouse id = -1 ...
	// ... then update corresponding percentage-value
	// ... otherwise: if warehouse in warehouselist
	// ... ... then update corresponding absolute-value and clear corresponding percentage value
	// ... ... otherwise report error
} else {
	$result['Message'] = 'insufficient arguments';
}
ob_end_clean();

// generate json
echo json_encode($result);
?>