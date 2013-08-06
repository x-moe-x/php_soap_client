$.fn.checkIntval = function() {
	var val = parseInt(this.val().replace(',', '.'));
	if (isNaN(val)) {
		this.val('not a number');
	} else {
		this.val(val);
	}

	return this;
};

$.fn.checkFloatval = function() {
	var val = parseFloat(this.val().replace(',', '.'));
	if (isNaN(val)) {
		this.val('not a number');
	} else {
		this.val(val);
	}

	return this;
};

$(document).ready(function() {

	var integerInputfields = $('#calculationTimeSingleWeighted, #calcualtionTimeDoubleWeighted, #minimumToleratedSpikes');
	var floatInputFields = $('#standardDeviationFaktor, #spikeTolerance');

	integerInputfields.change(function() {
		$(this).checkIntval();
	});
	
	floatInputFields.change(function(){
		$(this).checkFloatval();
	});

	integerInputfields.add(floatInputFields).mouseup(function(e) {
		e.preventDefault();
	}).focus(function() {
		if (isNaN($(this).val()))
			$(this).val("");
		else
			$(this).select();
	});
});
