<?php

/**
 * @param object $oObject
 * @param array $aValues
 * @param array $aOverrideValues
 * @return void
 */
function fillObjectFromArray(&$oObject, array $aValues, array $aOverrideValues = array()) {
	$aObjectVars = get_object_vars($oObject);
	foreach ($aObjectVars as $var => $oldValue) {
		if (!array_key_exists($var, $aOverrideValues)) {
			$oObject -> $var = isset($aValues[$var]) ? $aValues[$var] : NULL;
		} else {
			$oObject -> $var = isset($aOverrideValues[$var]) ? $aOverrideValues[$var] : NULL;
		}
	}
}
?>