<?php

class EanGenerator {

	/**
	 * @var int
	 */
	const DIGITS = 13;

	/**
	 * @var int
	 */
	const MAX_EAN = 9999999999999;

	/**
	 * @var int
	 */
	const MIN_EAN = 1000000000000;

	/**
	 * @var int
	 */
	private $baseEAN;

	/**
	 * @var int
	 */
	private $maxEANs;

	/**
	 * @param int $baseEAN
	 * @param int $maxEANs
	 * @return EanGenerator
	 */
	public function __construct($baseEan, $maxEANs) {
		$this -> baseEAN = floor($baseEan / 10) * 10;
		$this -> maxEANs = intval($maxEANs);
	}

	public function getEAN($EANNr) {
		if ($EANNr < $this -> maxEANs) {
			$resultEAN = $this -> baseEAN + $EANNr * 10;
			return $resultEAN + (10 - self::getSum($resultEAN) % 10) % 10;
		} else
			throw new RuntimeException("Not enough EANs!");
	}

	/**
	 * @var int $ean
	 * @return given ean valid?
	 */
	public static function valid($ean) {
		if (($ean > self::MAX_EAN) || ($ean < self::MIN_EAN))
			return false;
		// ean has wrong number of digits.

		return self::getSum($ean) % 10 === 0;
	}

	/**
	 * @param int $ean (13 digits required, last digit must be 0)
	 * @return int sum of digits: odds + evens * 3
	 */
	private static function getSum($ean) {
		$sum = 0;
		$pow = 1;
		for ($i = 0, $iMax = (int)(self::DIGITS + 1) / 2; $i < $iMax; ++$i) {
			$sum += (int)fmod(floor($ean / $pow), 10) + 3 * (int)fmod(floor($ean / ($pow * 10)), 10);
			$pow *= 100;
		}
		return $sum;
	}

}
?>
