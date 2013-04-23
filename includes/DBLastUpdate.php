<?php
function lastUpdateStart($functionName){
	// get lastupdate
	$query = 'SELECT * FROM MetaLastUpdate WHERE `Function` = \''. $functionName.'\'';

	$result = DBQuery::getInstance()->select($query)->fetchAssoc();

	$lastUpdate = intval($result['LastUpdate']);
	$currentTime = time();

	// store current timestamp
	$query = 'REPLACE INTO `MetaLastUpdate` '.
			DBUtils::buildInsert(
					array(
							'id'	=>  $result['id'],
							'Function'	=>	$functionName,
							'LastUpdate'	=> $lastUpdate,
							'CurrentLastUpdate'	=> $currentTime
					)
			);

	DBQuery::getInstance()->replace($query);

	return array($lastUpdate, $currentTime, $result['id']);
}

function lastUpdateFinish($id, $currentTime, $functionName)
{
	// store current timestamp
	$query = 'REPLACE INTO `MetaLastUpdate` '.
			DBUtils::buildInsert(
					array(
							'id'	=>  $id,
							'Function'	=>	$functionName,
							'LastUpdate'	=> $currentTime,
							'CurrentLastUpdate'	=> $currentTime
					)
			);

	DBQuery::getInstance()->replace($query);
}
?>