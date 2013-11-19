<?php
/**
 * extract config values form database
 *
 * @return array of config keys mapped on config values
 */
function getConfig() {
	$intValues = array('CalculationTimeA', 'CalculationTimeB', 'MinimumToleratedSpikesA', 'MinimumToleratedSpikesB');
	$floatValues = array('SpikeTolerance', 'StandardDeviationFactor');

	$query = 'SELECT
				* FROM `MetaConfig`
				WHERE';

	$nrOfIntVals = count($intValues);
	$nrOfFloatVals = count($floatValues);
	for ($i = 0; $i < $nrOfIntVals; ++$i){
		$query .= '
					`ConfigKey` = "'. $intValues[$i] .'" OR';
	}
	for ($i = 0; $i < $nrOfFloatVals; ++$i){
		$query .= '
					`ConfigKey` = "'. $floatValues[$i] .'" ' . ($i + 1 < $nrOfFloatVals ? 'OR':'');
	}

	$resultConfigQuery = DBQuery::getInstance() -> select($query);

	$result = array();
	for ($i = 0; $i < $resultConfigQuery -> getNumRows(); ++$i) {
		$configRow = $resultConfigQuery -> fetchAssoc();
		if ($configRow['ConfigKey'] == 'SpikeTolerance' || $configRow['ConfigKey'] == 'StandardDeviationFactor')
			$result[$configRow['ConfigKey']]['Value'] = floatval($configRow['ConfigValue']);
		else
			$result[$configRow['ConfigKey']]['Value'] = intval($configRow['ConfigValue']);

		$result[$configRow['ConfigKey']]['Active'] = intval($configRow['Active']);
	}

	foreach (array_merge($intValues, $floatValues) as $key) {
		if (!isset($result[$key]))
			throw new RuntimeException('Missing Config Key: ' . $key);
	}

	return $result;
}

?>