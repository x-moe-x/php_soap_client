$.fn.checkIntval = function() {'use strict';
	var val = parseInt(this.val().replace(',', '.'), 10);
	if (isNaN(val)) {
		this.val('not a number');
	} else {
		this.val(val);
	}

	return this;
};

$.fn.checkFloatval = function() {'use strict';
	var val = parseFloat(this.val().replace(',', '.'), 10);
	if (isNaN(val)) {
		this.val('not a number');
	} else {
		this.val(val);
	}

	return this;
};

$.fn.apiUpdate = function(path, type, elementPreprocess, elementPostprocess) {'use strict';
	var element, data;
	// preprocess data
	element = $(this);
	data = elementPreprocess(element, type);

	element.prop('disabled', true);

	// if data correct
	if (data !== 'incorrect') {
		// ... then send
		$.ajax({
			type : 'PUT',
			url : path + '/' + data.key + '/' + data.value
		}).done(function(putResult) {
			// when done: check for success ...
			if (putResult.success) {
				// ... then just set the value
				element.val(elementPostprocess(element, type, data, putResult.data));
			} else {
				// ... otherwise log error ...
				$('#errorMessages').append('<p>' + putResult.error + '</p>');
				// ... and try to get original value ...
				$.ajax({
					type : 'GET',
					url : path + '/' + data.key,
				}).done(function(getResult) {
					// when done: check for success ...
					if (getResult.success) {
						// ... then just set the unmodified value
						element.val(elementPostprocess(element, type, data, getResult.data));
					} else {
						// ... otherwise log error
						$('#errorMessages').append('<p>' + getResult.error + '</p>');
					}
				});
			}
			element.prop('disabled', false);
		});
	} else {
		// ... otherwise refuse
		element.val('Error');
		element.prop('disabled', false);
	}

	return this;
};
