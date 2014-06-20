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

$.fn.insertInput = function(inputID, unitString, changeEventHandler, externalValue) {'use strict';
	var setToValue;

	// if given: use external value, otherwise use content of current html element
	setToValue = typeof externalValue !== 'undefined' ? externalValue : $(this).text().trim();

	$(this).html($('<input/>', {
		id : inputID,
		value : setToValue,
		on : {
			focus : function() {
				if (isNaN($(this).val())) {
					$(this).val("");
				} else {
					$(this).select();
				}
			},
			change : changeEventHandler
		}
	})).append($('<label/>', {
		'class' : 'variableUnit',
		'for' : inputID,
		html : unitString
	}));

	return this;
};

function processMarking1ID(celDiv, sku) {'use strict';
	var status, marking1ID;

	status = [4, 9, 12, 16, 20];

	// get marking1ID ...
	marking1ID = parseInt($(celDiv).html(), 10);

	if ($.inArray(marking1ID, status) > -1) {
		// set class ...
		$(celDiv).addClass('marking1IDCell_' + marking1ID);

		// ... and clear cell afterwards
		$(celDiv).html('&nbsp;');
	} else if (marking1ID === 0) {
		$(celDiv).html('keine');
	} else {
		$(celDiv).html('FEHLER!');
	}
}

function elementPostProcessAmazonPrice(element, type, requestData, resultData) {'use strict';
	var returnValue;
	switch (type) {
		case 'float':
			returnValue = parseFloat(resultData.NewPrice);
			if (isNaN(returnValue)) {
				return 'error';
			} else {
				if (resultData.isChangePending) {
					element.parent().addClass('priceChanged');
				} else {
					element.parent().removeClass('priceChanged');
				}
				return returnValue.toFixed(2);
			}
			break;
		default:
			return 'error';
	}
}

function elementPostProcessGeneralCosts(element, type, requestData, resultData) {'use strict';
	var returnValue;
	switch (type) {
		case 'float':
			if (resultData['warehouseID'] == -1) {
				returnValue = resultData['value']['percentage'];
			} else {
				// clear corresponding percentage field
				$('#' + 'warehouseCost_automatic_' + resultData['warehouseID'] + '_' + resultData['date']).html(resultData['value']['percentage']);
				returnValue = resultData['value']['absolute'];
			}
			if (returnValue === null) {
				return '';
			} else {
				returnValue = parseFloat(returnValue);
				return isNaN(returnValue) ? 'error' : returnValue.toFixed(2);
			}
		default:
			return 'error';
	}
}

function elementProcessGeneralCosts(element, type) {'use strict';
	var id, matches;
	switch (type) {
		case 'float':
			element.checkFloatval();
			if (!isNaN(element.val())) {
				id = element.attr('id');
				if ( matches = id.match(/generalCosts_manual_(\d{8})/)) {
					return {
						key : '-1/' + matches[1],
						value : element.val()
					};
				} else if ( matches = id.match(/warehouseCost_manual_(\d+)_(\d{8})/)) {
					return {
						key : matches[1] + '/' + matches[2],
						value : element.val()
					};
				} else {
					return 'incorrect';
				}
			} else {
				return 'incorrect';
			}
		default:
			return 'incorrect';
	}
}

function elementProcessAmazonPrice(element) {'use strict';
	element.checkFloatval();
	return isNaN(element.val()) ? 'incorrect' : {
		key : element.attr('id'),
		value : element.val()
	};
}

function elementProcessStockConfig(element, type) {'use strict';
	switch (type) {
		case 'int':
			element.checkIntval();
			return isNaN(element.val()) ? 'incorrect' : {
				key : element.attr('id'),
				value : element.val()
			};
		case 'float':
			element.checkFloatval();
			return isNaN(element.val()) ? 'incorrect' : {
				key : element.attr('id'),
				value : element.val()
			};
		case 'percent':
			element.checkFloatval();
			return isNaN(element.val()) ? 'incorrect' : {
				key : element.attr('id'),
				value : element.val() / 100
			};
		case 'select':
			return {
				key: element.attr('id'),
				value : element.val()
			};
		default:
			return 'incorrect';
	}
};

function elementPostProcessStockConfig(element, type, requestData, resultData) {'use strict';
	var returnValue;
	switch (type) {
		case 'int':
			returnValue = parseInt(resultData[requestData.key]);
			return isNaN(returnValue) ? 'error' : returnValue;
		case 'float':
			returnValue = parseFloat(resultData[requestData.key]);
			return isNaN(returnValue) ? 'error' : returnValue;
		case 'percent':
			returnValue = parseFloat(resultData[requestData.key]);
			return isNaN(returnValue) ? 'error' : returnValue * 100;
		default:
			return 'error';
	}
}

function loadSuccess(result) {'use strict';
	$('body').removeClass("loading");
	$('#stockTable').flexReload();
	$('#errorMessages').append('<p> ' + result + '</p>');
}

function dialogify(buttonData) {'use strict';
	$.each(buttonData, function(index, button) {
		$(button.id).dialogify(button.title, button.descr, button.type, function() {
			$('body').addClass('loading');
			$.get('../api/execute/' + button.task, loadSuccess);
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

	$.each([{
		id : '#calculationTimeA',
		type : 'int',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#calculationTimeB',
		type : 'int',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#minimumToleratedSpikesA',
		type : 'int',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#minimumToleratedSpikesB',
		type : 'int',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#minimumOrdersA',
		type : 'int',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#minimumOrdersB',
		type : 'int',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#standardDeviationFactor',
		type : 'float',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#spikeTolerance',
		type : 'percent',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#calculationActive',
		type : 'select',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#writebackActive',
		type : 'select',
		path : '../api/config/stock',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}], function(index, input) {
		$(input.id).change(function() {
			$(this).apiUpdate(input.path, input.type, input.preprocess, input.postprocess);
		});
	});

	dialogify([{
		id : '#buttonManualUpdate',
		task : 'updateAll',
		title : 'Manuelle Aktualisierung anstossen?',
		descr : 'Aktualisierung der Artikel- und Rechnungsdaten. Dieser Vorgang kann einige Minuten in Anspruch nehmen.',
		type : 'normal'
	}, {
		id : '#buttonManualCalculate',
		task : 'calculateAll',
		title : 'Manuelle Kalkulation anstossen?',
		descr : 'Ermittelung des spitzenbreinigten Tagesbedarfes, der Rückschreibedaten sowie der Schreibberechtigungen. Dieser Vorgang kann einige Minuten in Anspruch nehmen.',
		type : 'normal'
	}, {
		id : '#buttonManualWriteBack',
		task : 'setAll',
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
			align : 'center',
			process: processMarking1ID
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

function prepareAmazon() {'use strict';
	$.each([{
		id : '#provisionCosts',
		type : 'percent',
		path : '../api/config/amazon',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#minimumMarge',
		type : 'percent',
		path : '../api/config/amazon',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}, {
		id : '#measuringTimeFrame',
		type : 'int',
		path : '../api/config/amazon',
		preprocess : elementProcessStockConfig,
		postprocess : elementPostProcessStockConfig
	}], function(index, input) {
		$(input.id).change(function() {
			$(this).apiUpdate(input.path, input.type, input.preprocess, input.postprocess);
		});
	});

	// create table
	$('#amazonTable').flexigrid({
		url : 'price-post-xml.php',
		dataType : 'xml',
		colModel : [{
			display : 'Item ID',
			name : 'ItemID',
			sortable : true
		},{
			display : 'Artikel Nr',
			name : 'ItemNo',
			sortable : true
		},{
			display : 'Name',
			name : 'ItemName'
		},{
			display : 'Markierung',
			name : 'Marking1ID',
			sortable : true,
			process: processMarking1ID
		},{
			display : 'Verkauf Stk. / 30 Tage<br>(vor) nach Änderung VK',
			name : 'M'
		},{
			display : 'durchschn. Marge / Stk. (mit aktuellen Kosten)<br>(vor) nach Änderung VK',
			name : 'N'
		},{
			display : 'Trend Artikel<br>verkaufte Stk',
			name : 'O'
		},{
			display : 'Trend Artikel(mit aktuellen Kosten))<br>Gewinn (Vgl. mit Herkunft + 1,8%)',
			name : 'P'
		},{
			display : 'Datum letzte Änderung VK<br>Zeitraum Trend (Soll / Ist)',
			name : 'Q'
		}, {
			display : 'alter Preis / aktueller Preis',
			name : 'Price',
			align : 'center',
			width : 120,
			process : function(cellDiv, SKU) {'use strict';
				var priceData, price;
				priceData = $.parseJSON($(cellDiv).html());

				$(cellDiv).html($('<span/>', {
					'class' : 'price oldPrice',
					html : priceData.oldPrice
				})).append($('<span/>', {
					'class' : 'valueDelimiter',
					html : '/'
				})).append($('<span/>', {
					'class' : 'price currentPrice',
					html : priceData.price
				})).addClass('amazonPrice');
			}
		}, {
			display : 'Min.- Preis<br>€',
			name : 'S'
		}, {
			display : '(Ziel-) Marge<br>%',
			name : 'T'
		}, {
			display : 'Preis ändern',
			name : 'ChangePrice',
			process : function(cellDiv, SKU) {
				var priceData;

				// extract current value
				priceData = $.parseJSON($(cellDiv).html());

				// add input field with current price plus unit
				$(cellDiv).insertInput(SKU, '€', function(event) {
					$(event.target).apiUpdate('../api/amazonPrice', 'float', elementProcessAmazonPrice, elementPostProcessAmazonPrice);
					$(cellDiv).addClass('priceChanged');
				}, parseFloat(priceData.price).toFixed(2));

				if (priceData.isChangePending) {
					$(cellDiv).addClass('priceChanged');
				}
			}
		}],
		height : 'auto',
		singleSelect : true,
		striped : false,
		title : 'Kalkulation Amazon',
		sortname : "ItemID",
		sortorder : "asc",
		usepager : true,
		useRp : true,
		rp : 20,
		rpOptions : [10, 20, 30, 50, 100, 200],
		pagetext : 'Seite',
		outof : 'von',
		pagestat : 'Zeige {from} bis {to} von {total} Artikeln',
		procmsg : 'Bitte warten...',
		buttons : [{
			name : 'Manuelles Schreiben auslösen',
			bclass : 'pReload',
			onpress : function(idOrName, gDiv) {
				$.get('../api/execute/setItemsPriceSets', function() {
					$('#amazonTable').flexReload();
				});
			}
		}, {
			name : 'Ungeschriebene Preisänderungen zurücksetzen',
			bclass : 'pInitAction',
			onpress : function(idOrName, gDiv) {
				$.get('../api/execute/resetPriceUpdates', function() {
					$('#amazonTable').flexReload();
				});
			}
		}]
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
				$(celDiv).insertInput('generalCosts_manual_' + id, '%', function(event) {
					$(event.target).apiUpdate('../api/generalCost', 'float', elementProcessGeneralCosts, elementPostProcessGeneralCosts);
				});
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
					$(celDiv).insertInput('warehouseCost_manual_' + warehouse.id + '_' + id, '€', function(event) {
						$(event.target).apiUpdate('../api/generalCost', 'float', elementProcessGeneralCosts, elementPostProcessGeneralCosts);
					});
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
						id : 'warehouseCost_automatic_' + warehouse.id + '_' + id,
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
	prepareAmazon();
	prepareGeneralCostConfig();

});
