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

$.fn.apiUpdate = function(path, type, elementPreprocess, valuePostprocess) {'use strict';
	var element, data;
	// preprocess data
	element = $(this);
	data = elementPreprocess($(this), type);

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
				if ( typeof valuePostprocess !== 'undefined') {
					element.val(valuePostprocess(putResult.data[data.key], type));
				} else {
					element.val(putResult.data[data.key]);
				}
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
						if ( typeof valuePostprocess !== 'undefined') {
							element.val(valuePostprocess(getResult.data[data.key], type));
						} else {
							element.val(getResult.data[key]);
						}
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
