<?php
function lastUpdateStart($functionName){
	// get lastupdate
	$query = 'SELECT * FROM MetaLastUpdate WHERE `Function` = \''. $functionName.'\'';

	$result = DBQuery::getInstance()->select($query)->fetchAssoc();

	$currentPage = intval($result['CurrentPage']);
	$lastUpdate = intval($result['LastUpdate']);
	$currentTime = time();

	// store current timestamp
	$query = 'UPDATE `MetaLastUpdate` '.
			DBUtils::buildUpdate(
					array(
							'CurrentLastUpdate'	=> $currentTime	
					)
			). ' WHERE `Function`=\''.$functionName.'\'';

	DBQuery::getInstance()->update($query);

	return array($lastUpdate, $currentTime, $currentPage);
}

function lastUpdatePageUpdate($functionName, $currentPage){
	$query = 'UPDATE `MetaLastUpdate` '.
			DBUtils::buildUpdate(
				array(
					'CurrentPage' => $currentPage
				)
			).' WHERE `Function`=\''.$functionName.'\'';

			DBQuery::getInstance()->update($query);
}

function lastUpdateFinish($currentTime, $functionName)
{
	// store current timestamp
	$query = 'UPDATE `MetaLastUpdate` '.
			DBUtils::buildUpdate(
					array(
							'LastUpdate'	=> $currentTime,
							'CurrentLastUpdate'	=> $currentTime,
							'CurrentPage' => 0
					)
			).' WHERE `Function`=\''.$functionName.'\'';

	DBQuery::getInstance()->update($query);
}
?>