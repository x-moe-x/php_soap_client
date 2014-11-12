var EanGenerator = function(baseEan, maxNrOfEan) {'use strict';
	this.baseEan = Math.floor(baseEan / 10) * 10;
	this.maxNrOfEan = maxNrOfEan;

	var DIGITS = 13, MAX_EAN = 9999999999999, MIN_EAN = 1000000000000, getSum = function(ean) {
		var sum = 0, i = null, processDigits = String(ean).split('');
		if (processDigits.length !== DIGITS) {
			throw 'Not enough Digits!';
		}
		for ( i = 0; i < DIGITS - 1; i += 2) {
			sum += parseInt(processDigits[i], 10) % 10 + 3 * parseInt(processDigits[i + 1], 10) % 10;
		}
		return sum + parseInt(processDigits[DIGITS - 1], 10) % 10;
	};

	this.getEan = function(itemID) {
		if (itemID >= this.maxNrOfEan) {
			throw 'Not enough EANs!';
		} else {
			var resultEan = this.baseEan;
			resultEan += itemID * 10;
			return resultEan + (10 - getSum(resultEan) % 10) % 10;
		}
	};

	this.valid = function(ean) {
		if ((ean > MAX_EAN) || (ean < MIN_EAN)) {
			return false;
		}
		return getSum(ean) % 10 === 0;
	};
};
