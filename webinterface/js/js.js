$.fn.dialogify = function(title, htmlText, type, okFunction) {'use strict';
	$(this).button().click(function() {
		$('#dialogText').html(htmlText);
		$('#dialogIcon').attr('class', (type === 'danger' ? 'ui-icon ui-icon-alert' : 'ui-icon ui-icon-info'));
		$('#dialog').dialog({
			title : title,
			modal : true,
			dialogClass : (type === 'danger' ? 'ui-state-error' : 'ui-state-highlight'),
			buttons : {
				OK : function() {
					okFunction();
					$(this).dialog("close");
				},
				Cancel : function() {
					$(this).dialog("close");
				}
			}
		});
	});
	return this;
};

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

$.fn.insertInput = function(inputID, unitString) {'use strict';
	$(this).html($('<input/>', {
		id : inputID,
		value : $(this).text(),
		on : {
			focus : function() {
				if (isNaN($(this).val())) {
					$(this).val("");
				} else {
					$(this).select();
				}
			},
			change : function() {
				$(this).checkFloatval().updateConfig(updateGeneralCostConfig);
			}
		}
	})).append($('<label/>', {
		'class' : 'variableUnit',
		'for' : inputID,
		html : unitString
	}));

	return this;
};

function updateRegularConfig(element, data) {
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

function updateGeneralCostConfig(element, data) {
	// disable field during post
	element.prop('disabled', true);

	$.post('updateGeneralCostConfig.php5', data, function(newConfig) {

		// re-enable field after post
		element.prop('disabled', false);
		if (newConfig.Message !== null) {
			$('#errorMessages').append($('<p/>', {
				html : newConfig.Message
			}));
			if (newConfig.Value !== null) {
				$(element).val(newConfig.Value);
			}
		}
	}, 'json');
}

$.fn.updateConfig = function(updateFunction) {'use strict';
	var data, element;

	if (!isNaN($(this).val())) {
		data = {
			key : $(this).attr('id'),
			value : $(this).val()
		};
		element = $(this);

		updateFunction(element, data);
	}

	return this;
};

function loadSuccess(result) {'use strict';
	$('body').removeClass("loading");
	$('#stockTable').flexReload();
	$('#errorMessages').append('<p> ' + result + '</p>');
}

function dialogify(buttonData) {'use strict';
	$.each(buttonData, function(index, button) {
		$(button.id).dialogify(button.title, button.descr, button.type, function() {
			$('body').addClass('loading');
			$.get('executeManual.php5', {
				action : button.task
			}, loadSuccess);
		});
	});
}

function updateify(inputData) {'use strict';
	$.each(inputData, function(index, input) {
		$(input.id).change(function() {

			if ((input.type === 'int') || (input.type === 'float')) {
				if (input.type === 'int') {
					$(this).checkIntval();
				} else {
					$(this).checkFloatval();
				}
				$(this).updateConfig(input.updateFunction);
				$(this).mouseup(function(e) {
					e.preventDefault();
				}).focus(function() {
					if (isNaN($(this).val())) {
						$(this).val("");
					} else {
						$(this).select();
					}
				});
			}
		});
	});
}

function prepareStock() {'use strict';
	$('.config').accordion({
		heightStyle : 'content',
		collapsible : true,
		active : false
	});

	$('.accordion').accordion({
		heightStyle : 'content'
	});

	$('#tabs').tabs({
		heightStyle : 'content'
	});

	updateify([{
		id : '#calculationTimeA',
		type : 'int',
		updateFunction : updateRegularConfig
	}, {
		id : '#calculationTimeB',
		type : 'int',
		updateFunction : updateRegularConfig
	}, {
		id : '#minimumToleratedSpikesA',
		type : 'int',
		updateFunction : updateRegularConfig
	}, {
		id : '#minimumToleratedSpikesB',
		type : 'int',
		updateFunction : updateRegularConfig
	}, {
		id : '#minimumOrdersA',
		type : 'int',
		updateFunction : updateRegularConfig
	}, {
		id : '#minimumOrdersB',
		type : 'int',
		updateFunction : updateRegularConfig
	}, {
		id : '#standardDeviationFactor',
		type : 'float',
		updateFunction : updateRegularConfig
	}, {
		id : '#spikeTolerance',
		type : 'float',
		updateFunction : updateRegularConfig
	}, {
		id : '#calculationActive',
		type : 'select',
		updateFunction : updateRegularConfig
	}, {
		id : '#writebackActive',
		type : 'select',
		updateFunction : updateRegularConfig
	}]);

	dialogify([{
		id : '#buttonManualUpdate',
		task : 'update',
		title : 'Manuelle Aktualisierung anstossen?',
		descr : 'Aktualisierung der Artikel- und Rechnungsdaten. Dieser Vorgang kann einige Minuten in Anspruch nehmen.',
		type : 'normal'
	}, {
		id : '#buttonManualCalculate',
		task : 'calculate',
		title : 'Manuelle Kalkulation anstossen?',
		descr : 'Ermittelung des spitzenbreinigten Tagesbedarfes, der Rückschreibedaten sowie der Schreibberechtigungen. Dieser Vorgang kann einige Minuten in Anspruch nehmen.',
		type : 'normal'
	}, {
		id : '#buttonManualWriteBack',
		task : 'writeBack',
		title : 'Manuelles Rückschreiben anstossen?',
		descr : 'Rückschreiben der Lieferanten- und Lagerdaten für schreibberechtigte Artikel',
		type : 'normal'
	}, {
		id : '#buttonResetArticles',
		task : 'resetArticles',
		title : 'Artikeldaten zurücksetzen?',
		descr : '<strong>Achtung:</strong><p>Wirklich alle Artikeldaten löschen?<br><br>Diese Aktion löscht ausschliesslich die Artikeldaten und stösst kein erneutes Update an!<p>',
		type : 'danger'
	}, {
		id : '#buttonResetOrders',
		task : 'resetOrders',
		title : 'Rechnugnsdaten zurücksetzen?',
		descr : 'Wirklich alle Rechnungsdaten löschen?<br><br>Diese Aktion löscht ausschliesslich die Rechnungsdaten und stösst kein erneutes Update an!',
		type : 'danger'
	}]);

	$('#stockTable').flexigrid({
		url : 'stock-post-xml.php5',
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
			align : 'left'
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
			display : 'Datum',
			name : 'Date',
			width : 100,
			sortable : true,
			align : 'right'
		}],
		buttons : [{
			name : 'Filter',
			bclass : 'filter'
		}],
		onSuccess : function(g) {
			var colModel, status, params;

			colModel = this.colModel;
			status = this.status;
			params = this.params;

			// post-processing of cells
			$('tbody tr td div', g.bDiv).each(function(index, newCell) {
				var colName;

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
									suggestionClass = 'writePermissionError';
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
				// change formatting
				$('.filter', g.tDiv).css({
					'padding-left' : 0
				});

				// encapsulate existing stuff
				$('.filter', g.tDiv).wrapInner('<span style="padding:0px"></span>');

				// append filter selection
				$('.filter', g.tDiv).append(function() {
					var filterSelection = '<div class=\'customButtonContent\'>';

					$.each(status, function(index, value) {
						filterSelection += '<div id="markingID_' + value + '"><input type="checkbox" id="markingID_' + value + '_field"></input><label for="markingID_' + value + '_field"></label></div>';
					});

					return filterSelection + '</div>';
				});

				$('.filter input[type=checkbox]', g.tDiv).change(function() {
					var filterMarking1D = [];

					// collect all checked marking filters
					$.each(status, function(index, value) {
						if ($('.filter #markingID_' + value + '_field').is(':checked')) {
							filterMarking1D.push(value);
						}
					});

					// adjust params
					params[0].value = filterMarking1D.join();

					// update grid
					g.populate();
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
		params : [{
			name : 'filterMarking1D',
			value : ''
		}],
		status : [4, 9, 12, 16, 20],
		sortname : "ItemID",
		sortorder : "asc",
		usepager : true,
		singleSelect : true,
		title : 'Bestandsautomatik',
		useRp : true,
		height : 500,
		rp : 20,
		rpOptions : [10, 20, 30, 50, 100, 200],
		showTableToggleBtn : false,
		pagetext : 'Seite',
		outof : 'von',
		pagestat : 'Zeige {from} bis {to} von {total} Artikeln',
		procmsg : 'Bitte warten...'
	});
}

function preparePrice() {'use strict';
	$('#priceTable').flexigrid({
		url : 'price-post-xml.php5',
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
			align : 'left'
		}, {
			display : 'Name',
			name : 'Name',
			width : 500,
			sortable : true,
			align : 'left'
		}, {
			display : 'Markierung',
			name : 'Marking1ID',
			width : 60,
			sortable : true,
			align : 'center'
		}, {
			display : 'Name / Herkunft',
			name : 'Referrer'
		}, {
			display : 'Ø Bedarf / Monat',
			name : 'MonthlyNeed'
		}, {
			display : 'pausiert (Grund)',
			name : 'PauseCause',
			hide : true
		}, {
			display : 'Trend Gewinn % im Zeitraum',
			name : 'x1',
			hide : true
		}, {
			display : 'Min.-Preis',
			name : 'x2',
			hide : true
		}, {
			display : 'aktueller Preis',
			name : 'CurrentPrice',
			hide : true
		}, {
			display : 'Preisvorschlag',
			name : 'x3',
			hide : true
		}, {
			display : 'Preis ändern',
			name : 'x4',
			hide : true
		}, {
			display : 'Function man. auslösen',
			name : 'x5',
			hide : true
		}],
		status : [4, 9, 12, 16, 20],
		sortname : "ItemID",
		sortorder : "asc",
		usepager : true,
		singleSelect : true,
		title : 'Preisautomatik',
		useRp : true,
		height : 500,
		rp : 20,
		rpOptions : [10, 20, 30, 50, 100, 200],
		showTableToggleBtn : false,
		pagetext : 'Seite',
		outof : 'von',
		pagestat : 'Zeige {from} bis {to} von {total} Artikeln',
		procmsg : 'Bitte warten...'
	});
}

function prepareGeneralCostConfig() {'use strict';

	// prepare col model
	var colModel = [{
		display : 'Monat',
		name : 'month',
		align : 'left',
		width : 60,
		process : function(celDiv, id) {
			$(celDiv).addClass('notModifyable');
		}
	}, {
		display : 'Allg. Betriebskosten',
		name : 'generalCosts_manual',
		align : 'right',
		width : 75,
		process : function(celDiv, id) {
			if (id !== 'Average') {
				$(celDiv).insertInput('generalCosts_manual_' + id, '%');
			} else {
				$(celDiv).addClass('notModifyable');
				if ($(celDiv).text().trim() !== '') {
					$(celDiv).wrapInner($('<span/>', {
						'class' : 'automatic_value'
					})).append($('<label/>', {
						'class' : 'variableUnit',
						html : '%'
					}));
				}
			}
		}
	}];

	$.each(warehouses, function(index, warehouse) {
		colModel.push({
			display : 'Transp./Lager<br>' + warehouse.name,
			name : 'warehouseCost_manual_' + warehouse.id,
			align : 'right',
			width : 110,
			process : function(celDiv, id) {
				if (id !== 'Average') {
					$(celDiv).insertInput('warehouseCost_manual_' + warehouse.id + '_' + id, '€');
				} else {
					$(celDiv).addClass('notModifyable');
					if ($(celDiv).text().trim() !== '') {
						$(celDiv).wrapInner($('<span/>', {
							'class' : 'automatic_value'
						})).append($('<label/>', {
							'class' : 'variableUnit',
							html : '€'
						}));
					}
				}
			}
		});
		colModel.push({
			display : 'Anteil Gesamtlstg.<br>' + warehouse.name,
			name : 'warehouseCost_automatic_' + warehouse.id,
			align : 'center',
			width : 110,
			process : function(celDiv, id) {
				$(celDiv).addClass('notModifyable');
				if ($(celDiv).text().trim() !== '') {
					$(celDiv).wrapInner($('<span/>', {
						'class' : 'automatic_value'
					})).append($('<label/>', {
						'class' : 'variableUnit',
						html : '%'
					}));
				}
			}
		});
	});

	// create table
	$('#runningCostConfiguration').flexigrid({
		url : 'runningCost-post-xml.php',
		dataType : 'xml',
		colModel : colModel,
		height : 'auto',
		singleSelect : true,
		striped : false,
		title : 'Betriebskosten',
		buttons : [{
			name : 'Update',
			bclass : 'pReload',
			onpress : function(idOrName, gDiv) {
				$('#runningCostConfiguration').flexReload();
			}
		}]
	});
}


$(document).ready(function() {'use strict';

	prepareStock();
	preparePrice();
	prepareGeneralCostConfig();

});
