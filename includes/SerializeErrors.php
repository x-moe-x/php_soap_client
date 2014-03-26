<?php

/**
 * @param array $aPlentySoapResponseMessages
 * @return string serialized error messages
 */
function serialize_errors(array $aPlentySoapResponseMessages) {
	$result = "ERROR-REPORT:\n";
	foreach ($aPlentySoapResponseMessages as $oPlentySoapResponseMessage) {/* @var $oPlentySoapResponseMessage PlentySoapResponseMessage */
		// for each non-success-message ...
		if ($oPlentySoapResponseMessage -> Code !== 100) {
			$result .= "\tCode:             \t$oPlentySoapResponseMessage->Code\n";
			if (isset($oPlentySoapResponseMessage -> IdentificationKey)) {
				$result .= "\tIdentificationKey:\t$oPlentySoapResponseMessage->IdentificationKey\n";
			}
			if (isset($oPlentySoapResponseMessage -> IdentificationValue)) {
				$result .= "\tIdentificationValue:\t$oPlentySoapResponseMessage->IdentificationValue\n";
			}
			if (isset($oPlentySoapResponseMessage -> ErrorMessages)) {
				$result .= "\tError Messages:\n";
				foreach ($oPlentySoapResponseMessage -> ErrorMessages->item as $oPlentySoapResponseSubMessage) {/* @var $oPlentySoapResponseSubMessage PlentySoapResponseSubMessage */
					$result .= "\t\t{$oPlentySoapResponseSubMessage->Key}: {$oPlentySoapResponseSubMessage->Value}\n";
				}
			}
			if (isset($oPlentySoapResponseMessage -> Warnings)) {
				$result .= "\tWarnings:\n";
				foreach ($oPlentySoapResponseMessage -> Warnings->item as $oPlentySoapResponseSubMessage) {/* @var $oPlentySoapResponseSubMessage PlentySoapResponseSubMessage */
					$result .= "\t\t{$oPlentySoapResponseSubMessage->Key}: {$oPlentySoapResponseSubMessage->Value}\n";
				}
			}
			if (isset($oPlentySoapResponseMessage -> SuccessMessages)) {
				$result .= "\tSuccess Messages:\n";
				foreach ($oPlentySoapResponseMessage -> SuccessMessages->item as $oPlentySoapResponseSubMessage) {/* @var $oPlentySoapResponseSubMessage PlentySoapResponseSubMessage */
					$result .= "\t\t{$oPlentySoapResponseSubMessage->Key}: {$oPlentySoapResponseSubMessage->Value}\n";
				}
			}
		}
	}
	return $result;
}
?>