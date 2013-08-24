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

$.fn.updateConfig = function() {
	if (!isNaN($(this).val())) {
		var data = {
			key : $(this).attr('id'),
			value : $(this).val()
		};

		console.log("trying to send data", data);

		var element = $(this);

		// disable field during post
		element.prop('disabled',true);

		$.post('updateConfig.php5', data, function(result) {

			// re-enable field after post
			element.prop('disabled',false);
			if (result != "") {
				alert(result);
			}
		});
	}

	return this;
};

$(document).ready(function() {

	var integerInputfields = $('#calculationTimeSingleWeighted, #calcualtionTimeDoubleWeighted, #minimumToleratedSpikes');
	var floatInputFields = $('#standardDeviationFactor, #spikeTolerance');

	integerInputfields.change(function() {
		$(this).checkIntval();
		$(this).updateConfig();
	});

	floatInputFields.change(function() {
		$(this).checkFloatval();
		$(this).updateConfig();
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
