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

$.fn.updateConfig = function() {'use strict';
	var data, element;

	if (!isNaN($(this).val())) {
		data = {
			key : $(this).attr('id'),
			value : $(this).val()
		};
		element = $(this);

		// disable field during post
		element.prop('disabled', true);

		$.post('updateConfig.php5', data, function(newConfig) {

			// re-enable field after post
			element.prop('disabled', false);
			if (newConfig.Message !== null) {
				$('#errorMessages').append('<p>' + newConfig.Message + '</p>');
				if (newConfig.Value !== null) {
					$(element).val(newConfig.Value);
				}
			}
		}, 'json');
	}

	return this;
};

$(document).ready(function() {'use strict';
	var integerInputfields, floatInputFields;

	integerInputfields = $('#calculationTimeA, #calculationTimeB, #minimumToleratedSpikesA, #minimumToleratedSpikesB, #minimumOrdersA, #minimumOrdersB');
	floatInputFields = $('#standardDeviationFactor, #spikeTolerance');

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
		if (isNaN($(this).val())) {
			$(this).val("");
		} else {
			$(this).select();
		}
	});

	$('#resultTable').flexigrid({
		url : 'post-xml.php5',
		dataType : 'xml',
		colModel : [{
			display : 'Item ID',
			name : 'ItemID',
			width : 40,
			sortable : true,
			align : 'center'
		}, {
			display : 'Artikel Nr',
			name : 'ItemNo',
			width : 80,
			sortable : true,
			align : 'center'
		}, {
			display : 'Name',
			name : 'Name',
			width : 500,
			sortable : true,
			align : 'left'
		}, {
			display : 'Rohdaten A',
			name : 'RawDataA',
			width : 120,
			sortable : false,
			align : 'left'
		}, {
			display : 'Rohdaten B',
			name : 'RawDataB',
			width : 120,
			sortable : false,
			align : 'left'
		}, {
			display : 'Ø Bedarf / Monat',
			name : 'MonthlyNeed',
			width : 60,
			sortable : true,
			align : 'right'
		}, {
			display : 'Ø Bedarf / Tag',
			name : 'DailyNeed',
			width : 60,
			sortable : true,
			align : 'right'
		}, {
			display : 'Markierung',
			name : 'Marking',
			width : 60,
			sortable : true,
			align : 'center'
		}, {
			display : 'Meldebest.<br>(neu / alt)',
			name : 'reorder_level_suggestion',
			width : 60,
			sortable : false,
			align : 'right'
		}, {
			display : 'Max.best.<br>(neu / alt)',
			name : 'max_stock_suggestion',
			width : 80,
			sortable : false,
			align : 'right',
			hide : false
		}, {
			display : 'Mindesabn.<br>(neu / alt)',
			name : 'min_purchase_order_suggestion',
			width : 60,
			sortable : false,
			align : 'right',
			hide : false
		}, {
			display : 'VPE',
			name : 'vpe',
			width : 40,
			sortable : false,
			align : 'right',
			hide : false
		}, {
			display : 'Änderung',
			name : 'change',
			width : 80,
			sortable : false,
			align : 'right',
			hide : true
		}, {
			display : 'Status Meldebestand',
			name : 'status_reorder_level',
			width : 80,
			sortable : false,
			align : 'right',
			hide : true
		}, {
			display : 'Datum',
			name : 'Date',
			width : 100,
			sortable : true,
			align : 'right'
		}],
		buttons : [{
			name : 'Kalkulation manuell auslösen',
			bclass : 'gear',
			onpress : function() {
				$('body').addClass('loading');
				$.get('executeCalculation.php5', function(result) {
					$('body').removeClass("loading");
					$('#resultTable').flexReload();
					$('#errorMessages').append('<p> calculation took ' + result.executionTime + ' ' + result.executionTimeUnit + '</p>');
				}, 'json');
			}
		}, {
			separator : true
		}, {
			name : 'Filter',
			bclass : 'filter'
		}],
		onSuccess : function(g) {
			var colModel, status;

			colModel = this.colModel;
			status = this.params.status;

			// post-processing of cells
			$('tbody tr td', g.bDiv).each(function(index) {
				var newCell, colName;

				newCell = $(this).find('div');
				colName = colModel[index % colModel.length].name;

				// visualize rawdata
				if ((colName === 'RawDataA') || (colName === 'RawDataB')) {( function() {

							var dataTokens, skipped, data, totalSum, dataString;

							dataTokens = $(newCell).text().split(':');
							if (dataTokens.length === 2) {

								// extract # of skipped orders
								skipped = parseInt(dataTokens[0], 10);

								// extract per-order-quantities
								data = dataTokens[1].split(',');

								// prepare output
								totalSum = 0;
								dataString = '<ul>';

								// for each of the pre-order-quantities ...
								$.each(data, function(index, value) {
									// ... add it's value to the prepared output
									dataString += '<li class="' + (index < skipped ? 'skipped' : 'counted') + '">' + value + '</li>';
									totalSum += parseInt(value, 10);
								});

								$(newCell).html('<span class="totalSum">' + totalSum + ' = </span>' + dataString + '</ul>');
							}

						}());

					// adjust marking to display colors instead numbers
				} else if (colName === 'Marking') {( function() {
							var id;

							// get id ...
							id = parseInt($(newCell).html(), 10);

							if ($.inArray(id, status) > -1) {
								// set class ...
								$(newCell).addClass('markingIDCell_' + id);

								// ... and clear cell afterwards
								$(newCell).html('&nbsp;');
							} else if (id === 0) {
								$(newCell).html('keine');
							} else {
								$(newCell).html('FEHLER!');
							}
						}());
					// adjust suggestions to visualize permissions and errors
				} else if ((colName === 'reorder_level_suggestion') || (colName === 'max_stock_suggestion') || (colName === 'min_purchase_order_suggestion')) {( function() {
							var dataTokens, suggestionClass;
							dataTokens = $(newCell).text().split(':');
							if (dataTokens.length === 2) {
								if (dataTokens[0] === 'e') {
									suggestionClass = 'noWritePermission';
								} else {
									suggestionClass = 'noSuggestion';
								}
								$(newCell).html('<span class="' + suggestionClass + '">' + dataTokens[1] + '</span>');
							} else if (dataTokens.length === 3) {
								if (dataTokens[0] === 'w') {
									suggestionClass = 'writePermission';
								} else if (dataTokens[0] === 'x') {
									if (dataTokens[1] === dataTokens[2]) {
										suggestionClass = 'noSuggestion';
									} else {
										suggestionClass = 'noWritePermission';
									}
								} else {
									// dataTokens[0] === 'e' or anything else
									suggestionClass = 'writePermissionError';
								}
								$(newCell).html('<span class="' + suggestionClass + '">' + dataTokens[1] + '</span> / ' + dataTokens[2]);
							}
						}());
				}
			});

			if ($('.filter input', g.tDiv).length === 0) {

				// append filter selection
				$('.filter', g.tDiv).append(function() {
					var filterSelection = '';

					$.each(status, function(index, value) {
						filterSelection += '<div id="markingID_' + value + '"><input type="checkbox" id="markingID_' + value + '_field"></input><label for="markingID_' + value + '_field"></label></div>';
					});

					// filterSelection += '<div>alle</div><div>keine</div>';

					return filterSelection;
				});

				$('.filter input[type=checkbox]', g.tDiv).change(function() {
					// collect all data
					var data = [];

					$.each(status, function(index, value) {
						data.push({
							id : value,
							checked : $('.filter #markingID_' + value + '_field').is(':checked')
						});
					});

					// update query
					$.post('#', data, function() {
						$('#resultTable').flexReload();
					});
				});
			}
		},
		searchitems : [{
			display : 'ItemID',
			name : 'ItemID'
		}, {
			display : 'Artikel Nr',
			name : 'ItemNo'
		}, {
			display : 'Name',
			name : 'Name',
			isdefault : true
		}],
		params : {
			status : [4, 9, 12, 16, 20]
		},
		sortname : "ItemID",
		sortorder : "asc",
		usepager : true,
		singleSelect : true,
		title : 'Artikel',
		useRp : true,
		rp : 20,
		rpOptions : [10, 20, 30, 50, 100, 200],
		showTableToggleBtn : false,
		pagetext : 'Seite',
		outof : 'von',
		pagestat : 'Zeige {from} bis {to} von {total} Artikeln',
		procmsg : 'Bitte warten...'
	});
	( function() {
			var configDiv = $('#variableManipulation');
			$(configDiv).hide();
			$('#toggleConfig').click(function() {
				$(configDiv).toggle('blind', 500);
			});
		}());
});
