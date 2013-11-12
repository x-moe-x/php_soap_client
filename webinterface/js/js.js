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

		$.post('updateConfig.php5', data, function(result) {

			// re-enable field after post
			element.prop('disabled', false);
			if (result !== '') {
				alert(result);
			}
		});
	}

	return this;
};

$(document).ready(function() {'use strict';
	var integerInputfields, floatInputFields;

	integerInputfields = $('#calculationTimeA, #calcualtionTimeB, #minimumToleratedSpikesA, #minimumToleratedSpikesB');
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
		},{
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
			display : 'Rohdaten',
			name : 'RawData',
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
			display : 'Empf. Meldebest. (Meldebest. alt)',
			name : 'reorder_level_suggestion',
			width : 60,
			sortable : false,
			align : 'right'
		}, {
			display : 'Mindesabn. / Best.-vorschlag (Best.-vorschlag akt.)',
			name : 'min_purchase_order_suggestion',
			width : 60,
			sortable : false,
			align : 'right',
			hide : false
		}, {
			display : 'Empf. Max.best.',
			name : 'max_stock_suggestion',
			width : 80,
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
				$.get('executeCalculation.php5', function() {
					$('body').removeClass("loading");
					$('#resultTable').flexReload();
				});
			}
		}, {
			separator : true
		}, {
			name : 'Filter',
			bclass : 'filter'
		}],
		onSuccess : function(g) {
			var rawDataTotalSumMaxSize, colModel, status;

			rawDataTotalSumMaxSize = 0;
			colModel = this.colModel;
			status = this.params.status;

			// post-processing of cells
			$('tbody tr td', g.bDiv).each(function(index) {
				var newCell, colName, dataTokens, skipped, data, totalSum, dataString, id;

				newCell = $(this).find('div');
				colName = colModel[index % colModel.length].name;

				// visualize rawdata
				if (colName === 'RawData') {
					dataTokens = $(this).text().split(':');
					if (dataTokens.length === 2) {
						skipped = parseInt(dataTokens[0], 10);
						data = dataTokens[1].split(',');
						totalSum = 0;
						dataString = skipped > 0 ? '<ul class="skipped">' : '<ul class="counted">';

						$.each(data, function(index, value) {
							dataString += '<li>' + value + '</li>';
							if (index + 1 === skipped) {
								dataString += '</ul><ul class="counted">';
							}
							totalSum += parseInt(value, 10);
						});

						$(newCell).html('<span class="totalSum">' + totalSum + ' = </span>' + dataString + '</ul>');

						rawDataTotalSumMaxSize = Math.max($('.totalSum', newCell).outerWidth(), rawDataTotalSumMaxSize);
					}
					// adjust marking to display colors instead numbers
				} else if (colName === 'Marking') {
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

			// adjust width of totalsum fields in rawdata to the same size
			$('.totalSum', g.bDiv).each(function() {
				$(this).outerWidth(rawDataTotalSumMaxSize);
			});

			// adjust table height
			// $('#resultTable').parent().css('height', $('#resultTable').outerHeight());
		},
		searchitems : [{
			display : 'ItemID',
			name : 'ItemID'
		},{
			display : 'Artikel Nr',
			name : 'ItemNo'
		}, {
			display : 'Name',
			name : 'Name',
			isdefault : true
		}],
		params : {
			status : [4, 9, 16, 20]
		},
		sortname : "ItemID",
		sortorder : "asc",
		usepager : true,
		singleSelect : true,
		title : 'Artikel',
		useRp : true,
		rp : 15,
		showTableToggleBtn : false,
		pagetext : 'Seite',
		outof : 'von',
		pagestat : 'Zeige {from} bis {to} von {total} Artikeln',
		procmsg : 'Bitte warten...'
	});
});
