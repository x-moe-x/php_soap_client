$.fn.apiUpdate = function(path, type, elementPreprocess, valuePostprocess) {'use strict';
	var element, key, value;
	// preprocess data
	element = $(this);
	key = $(this).attr('id');
	value = elementPreprocess($(this), type);

	element.prop('disabled', true);

	// if data correct
	if (value !== 'incorrect') {
		// ... then send
		$.ajax({
			type : 'PUT',
			url : path + '/' + key + '/' + value
		}).done(function(putResult) {
			// when done: check for success ...
			if (putResult.success) {
				// ... then just set the value
				if ( typeof valuePostprocess !== 'undefined') {
					element.val(valuePostprocess(putResult.data[key], type));
				} else {
					element.val(putResult.data[key]);
				}
			} else {
				// ... otherwise log error ...
				$('#errorMessages').append('<p>' + putResult.error + '</p>');
				// ... and try to get original value ...
				$.ajax({
					type : 'GET',
					url : path + '/' + key,
				}).done(function(getResult) {
					// when done: check for success ...
					if (getResult.success) {
						// ... then just set the unmodified value
						if ( typeof valuePostprocess !== 'undefined') {
							element.val(valuePostprocess(getResult.data[key], type));
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
