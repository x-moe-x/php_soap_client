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

function addMarking1IDFilter(g, status, params, identifyingClass) {'use strict';
	if ($('.' + identifyingClass + ' input', g.tDiv).length === 0) {

		// change formatting
		$('.' + identifyingClass, g.tDiv).css({
			'padding-left' : 0
		})
		// encapsulate existing stuff
		.wrapInner($('<span/>', {
			css : {
				'padding' : '0'
			}
		}))
		// append filter selection
		.append(function() {
			var filterSelection = $('<div/>', {
				'class' : 'customButtonContent'
			});

			$.each(status, function(index, value) {
				filterSelection.append($('<div/>', {
					'class' : 'markingID_' + value
				})
				// insert input ...
				.append($('<input/>', {
					id : 'markingID_' + value + '_field_' + identifyingClass,
					type : 'checkbox',
					on : {
						change : function() {
							var filterMarking1ID = [];

							// collect all checked marking filters
							$.each(status, function(index, value) {
								if ($('.' + identifyingClass + ' #markingID_' + value + '_field_' + identifyingClass).is(':checked')) {
									filterMarking1ID.push(value);
								}
							});

							// adjust params
							params[0].value = filterMarking1ID.join();

							// update grid
							g.populate();
						}
					}
				}))
				// ... and label
				.append($('<label/>', {
					'for' : 'markingID_' + value + '_field_' + identifyingClass
				})));
			});

			return filterSelection;
		});
	}
}

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
				key : element.attr('id'),
				value : element.val()
			};
		default:
			return 'incorrect';
	}
}

function elementPostProcessStockConfig(element, type, requestData, resultData) {'use strict';
	var returnValue;
	switch (type) {
		case 'int':
			returnValue = parseInt(resultData[requestData.key], 10);
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

function prepareStock() {'use strict';

	function loadSuccess(result) {
		$('body').removeClass("loading");
		$('#stockTable').flexReload();
		$('#errorMessages').append('<p> ' + result + '</p>');
	}

	function dialogify(buttonData) {
		$.each(buttonData, function(index, button) {
			$(button.id).dialogify(button.title, button.descr, button.type, function() {
				$('body').addClass('loading');
				$.get('../api/execute/' + button.task, loadSuccess);
			});
		});
	}


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
			process : processMarking1ID
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
			bclass : 'filter stockFilter'
		}],
		onSuccess : function(g) {
			var colModel, status, params, pSearch;

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

			addMarking1IDFilter(g, status, params, 'stockFilter');

			// start with searchbar visible
			pSearch = $('.pSearch', g.pDiv);
			if (!pSearch.data('initialized')) {
				pSearch.click();
				pSearch.data('initialized', true);
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
			sortable : true,
			width : 35,
			align : 'center'
		}, {
			display : 'Artikel Nr',
			name : 'ItemNo',
			sortable : true,
			width : 90
		}, {
			display : 'Name',
			name : 'ItemName',
			sortable : true,
			width : 250
		}, {
			display : 'Markierung',
			name : 'Marking1ID',
			sortable : true,
			width : 60,
			process : processMarking1ID
		}, {
			display : 'Verkauf Stk. / 30 Tage<br>(vor) nach Änderung VK',
			name : 'Quantities',
			sortable : true,
			process : function(cellDiv, SKU) {
				var quantityData, valueQuality;
				quantityData = $.parseJSON($(cellDiv).html());

				if (quantityData.oldQuantity <= quantityData.newQuantity) {
					valueQuality = 'goodValue';
				}
				if (quantityData.oldQuantity > quantityData.newQuantity) {
					valueQuality = 'badValue';
				}

				$(cellDiv).html($('<span/>', {
					html : quantityData.oldQuantity,
					'class' : 'oldValue'
				})).append($('<span/>', {
					html : quantityData.newQuantity,
					'class' : 'newValue ' + valueQuality
				}));
			}
		}, {
			display : 'Marge / Stk. (mit aktuellen Kosten)<br>(vor) nach Änderung VK',
			name : 'Marge',
			sortable : true,
			width : 120,
			process : function(cellDiv, SKU) {
				var margeData, valueQuality;

				margeData = $.parseJSON($(cellDiv).html());

				if (!margeData.isPriceValid) {
					$(cellDiv).html($('<span/>', {
						html : 'Invalid price',
						'class' : 'badValue'
					}));
					return;
				}

				if (margeData.oldMarge <= margeData.newMarge) {
					valueQuality = 'goodValue';
				}
				if (margeData.oldMarge > margeData.newMarge) {
					valueQuality = 'badValue';
				}

				$(cellDiv).html($('<span/>', {
					html : (margeData.oldMarge * 100).toFixed(2),
					'class' : 'oldMarge'
				})).append($('<span/>', {
					html : (margeData.newMarge * 100).toFixed(2),
					'class' : 'newMarge ' + valueQuality
				}));
			}
		}, {
			display : 'Trend Artikel<br>verkaufte Stk',
			name : 'Trend',
			sortable : true,
			process : function(cellDiv, SKU) {
				var trendValue;

				trendValue = parseFloat($(cellDiv).html());
				if (trendValue !== Infinity) {
					$(cellDiv).html($('<span/>', {
						html : (trendValue * 100).toFixed(2),
						'class' : 'trendValue ' + (trendValue >= 0 ? 'goodValue' : 'badValue')
					}));
				} else {
					$(cellDiv).html($('<span/>', {
						html : '&infin;',
						'class' : 'infinity goodValue'
					}));
				}
			}
		}, {
			display : 'Trend Artikel(mit aktuellen Kosten)<br>Gewinn',
			name : 'TrendProfit',
			sortable : true,
			process : function(cellDiv, SKU) {
				var trendProfitData;

				trendProfitData = $.parseJSON($(cellDiv).html());

				if (!trendProfitData.isPriceValid) {
					$(cellDiv).html($('<span/>', {
						html : 'Invalid price',
						'class' : 'badValue'
					}));
				} else if (parseFloat(trendProfitData.TrendProfitValue) !== Infinity) {
					$(cellDiv).html($('<span/>', {
						html : (trendProfitData.TrendProfitValue * 100).toFixed(2),
						'class' : 'trendProfitValue ' + (trendProfitData.TrendProfitValue >= 0 ? 'goodValue' : 'badValue')
					}));
				} else {
					$(cellDiv).html($('<span/>', {
						html : '&infin;',
						'class' : 'infinity goodValue'
					}));
				}

			}
		}, {
			display : 'Datum letzte Änderung VK<br>Zeitraum Trend (Soll / Ist)',
			name : 'TimeData',
			sortable : true,
			process : function(cellDiv, SKU) {
				var timeData;

				timeData = $.parseJSON($(cellDiv).html());

				$(cellDiv).html($('<span/>', {
					html : timeData.targetDays,
					'class' : 'targetDaysValue'
				}).after($('<span/>', {
					'class' : 'valueDelimiter',
					html : '/'
				})).after($('<span/>', {
					html : timeData.currentDays,
					'class' : 'currentDaysValue ' + (timeData.targetDays > timeData.currentDays ? 'goodValue' : 'badValue')
				})).after($('<span/>', {
					'class' : 'ui-icon ui-icon-help',
					style : 'display: inline-block',
					title : 'Änderungsdatum: ' + timeData.writtenTime
				}).tooltip()));
			}
		}, {
			display : 'Preis: alt / aktuell',
			name : 'Price',
			align : 'center',
			width : 120,
			process : function(cellDiv, SKU) {
				var priceData, price;
				priceData = $.parseJSON($(cellDiv).html());

				if (!priceData.isPriceValid) {
					$(cellDiv).html($('<span/>', {
						html : 'Invalid price',
						'class' : 'badValue'
					}));
					return;
				}

				$(cellDiv).html($('<span/>', {
					'class' : 'price oldPrice',
					html : parseFloat(priceData.oldPrice).toFixed(2)
				})).append($('<span/>', {
					'class' : 'valueDelimiter',
					html : '/'
				})).append($('<span/>', {
					'class' : 'price currentPrice',
					html : parseFloat(priceData.price).toFixed(2)
				})).addClass('amazonPrice');
			}
		}, {
			display : 'Min.- Preis',
			name : 'MinPrice',
			width : 70,
			sortable : true,
			process : function(cellDiv, SKU) {
				var minPrice;

				minPrice = parseFloat($(cellDiv).html()).toFixed(2);

				$(cellDiv).html($('<span/>', {
					'class' : 'minPrice' + (minPrice > 0 ? '' : ' badValue'),
					html : minPrice > 0 ? minPrice : 'no EK given'
				}));
			}
		}, {
			display : 'Std. Preis',
			name : 'StandardPrice',
			width : 70,
			sortable : true,
			process : function(cellDiv, SKU) {
				var standardPrice;

				standardPrice = parseFloat($(cellDiv).html()).toFixed(2);

				$(cellDiv).html($('<span/>', {
					'class' : 'standardPrice' + (standardPrice > 0 ? '' : ' badValue'),
					html : standardPrice > 0 ? standardPrice : 'no StandardPrice given'
				}));
			}
		}, {
			display : '(Ziel-) Marge',
			name : 'TargetMarge',
			sortable : true,
			width : 70,
			//sortable : true,
			process : function(cellDiv, SKU) {
				$(cellDiv).empty().attr('id', 'changeMarge_' + SKU);
			}
		}, {
			display : 'Preis ändern, netto',
			name : 'ChangePrice',
			sortable : true,
			width : 70,
			//sortable : true,
			process : function(cellDiv, SKU) {
				$(cellDiv).empty().attr('id', 'changeNetto_' + SKU);
			}
		}, {
			display : 'Preis ändern, brutto',
			name : 'ChangePriceBrutto',
			sortable : true,
			width : 70,
			//sortable : true,
			process : function(cellDiv, SKU) {
				var priceData;

				priceData = $.parseJSON($(cellDiv).html());

				$(cellDiv).empty().attr('id', 'changeBrutto_' + SKU);

				// fill netto value
				$('#changeNetto_' + SKU).insertInput('inputNetto_' + SKU, '€', function(event) {
					$(event.target).apiUpdate('../api/amazonPrice', 'float', function(element, type) {
						var SKUMatches;

						element.checkFloatval();

						if ((( SKUMatches = element.attr('id').match(/inputNetto_(\d+-\d+-\d+)/)) === null) || (type !== 'float') || isNaN(element.val())) {
							return 'incorrect';
						}

						return {
							key : SKUMatches[1],
							value : element.val()
						};
					}, function(element, type, requestData, resultData) {
						var returnValue;

						returnValue = parseFloat(resultData.NewPrice);
						if (type !== 'float' || isNaN(returnValue)) {
							return 'error';
						}

						return returnValue.toFixed(2);
					});
					$('#amazonTable').flexReload();
				}, priceData.isPriceValid ? parseFloat(priceData.price).toFixed(2) : '');

				// fill marge
				$('#changeMarge_' + SKU).insertInput('inputMarge_' + SKU, '%', function(event) {
					$(event.target).apiUpdate('../api/amazonPrice', 'percent', function(element, type) {
						var SKUMatches;

						element.checkFloatval();

						if ((( SKUMatches = element.attr('id').match(/inputMarge_(\d+-\d+-\d+)/)) === null) || (type !== 'percent') || isNaN(element.val())) {
							return 'incorrect';
						}

						return {
							key : SKUMatches[1],
							value : priceData.purchasePrice / (1 - (priceData.fixedPercentage + element.val() / 100))
						};
					}, function(element, type, requestData, resultData) {
						var returnValue;

						returnValue = parseFloat(resultData.NewPrice);
						if (type !== 'percent' || isNaN(returnValue)) {
							return 'error';
						}

						return parseFloat((1 - (priceData.purchasePrice / returnValue + priceData.fixedPercentage)) * 100).toFixed(2);
					});
					$('#amazonTable').flexReload();
				}, priceData.isPriceValid ? parseFloat((1 - (priceData.purchasePrice / priceData.price + priceData.fixedPercentage)) * 100).toFixed(2) : '');

				// fill brutto
				$(cellDiv).insertInput('inputBrutto_' + SKU, '€', function(event) {
					$(event.target).apiUpdate('../api/amazonPrice', 'float', function(element, type) {
						var SKUMatches;

						element.checkFloatval();

						if ((( SKUMatches = element.attr('id').match(/inputBrutto_(\d+-\d+-\d+)/)) === null) || (type !== 'float') || isNaN(element.val())) {
							return 'incorrect';
						}

						return {
							key : SKUMatches[1],
							value : element.val() / priceData.vat
						};
					}, function(element, type, requestData, resultData) {
						var returnValue;

						returnValue = parseFloat(resultData.NewPrice);
						if (type !== 'float' || isNaN(returnValue)) {
							return 'error';
						}

						return parseFloat(returnValue * priceData.vat).toFixed(2);
					});
					$('#amazonTable').flexReload();
				}, priceData.isPriceValid ? parseFloat(priceData.price * priceData.vat).toFixed(2) : '');

				// change row coloring
				if (priceData.isChangePending) {
					$('#row' + SKU).addClass('priceChanged');
				}
			}
		}],
		searchitems : [{
			display : 'ItemID',
			name : 'ItemID'
		}, {
			display : 'Artikel Nr.',
			name : 'ItemNo'
		}, {
			display : 'Name (UND verknüpft!)',
			name : 'ItemName'
		}],
		height : '500',
		singleSelect : true,
		striped : true,
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
		status : [4, 9, 12, 16, 20],
		params : [{
			name : 'filterMarking1D',
			value : ''
		}],
		buttons : [{
			name : 'Filter',
			bclass : 'filter amazonFilter'
		}, {
			name : 'Manuelles Schreiben auslösen',
			bclass : 'pInitAction',
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
		}],
		onSuccess : function(g) {
			var pSearch;

			addMarking1IDFilter(g, this.status, this.params, 'amazonFilter');

			// start with searchbar visible
			pSearch = $('.pSearch', g.pDiv);
			if (!pSearch.data('initialized')) {
				pSearch.click();
				pSearch.data('initialized', true);
			}
		}
	});
}

function prepareGeneralCostConfig() {'use strict';
	var colModel, processFunctions;

	processFunctions = {
		preProcess : function(element, type) {
			var id, matches;

			element.checkFloatval();
			if (type !== 'float' || isNaN(element.val())) {
				return 'incorrect';
			}

			id = element.attr('id');
			if (( matches = id.match(/generalCosts_manual_(\d{8})/)) !== null) {
				return {
					key : '-1/' + matches[1],
					value : element.val()
				};
			}
			if (( matches = id.match(/warehouseCost_manual_(\d+)_(\d{8})/)) !== null) {
				return {
					key : matches[1] + '/' + matches[2],
					value : element.val()
				};
			}
			return 'incorrect';

		},
		postProcess : function(element, type, requestData, resultData) {
			var returnValue;

			if (type !== 'float') {
				return 'error';
			}

			if (resultData.warehouseID === -1) {
				returnValue = resultData.value.percentage;
			} else {
				returnValue = resultData.value.absolute;
			}

			if (returnValue === null) {
				// clear corresponding percentage field
				$('#' + 'warehouseCost_automatic_' + resultData.warehouseID + '_' + resultData.date).empty();

				// hide unit
				$('#' + 'warehouseCost_automatic_label_' + resultData.warehouseID + '_' + resultData.date).addClass('invisible');

				// hide shipping hint
				$('#' + 'warehouseCost_automatic_tooltip_' + resultData.warehouseID + '_' + resultData.date).addClass('invisible');
				return '';
			}

			if (resultData.warehouseID !== -1) {
				// fill corresponding percentage field
				$('#' + 'warehouseCost_automatic_' + resultData.warehouseID + '_' + resultData.date).html(resultData.value.percentageShippingRevenueCleared.percentage);

				// show unit
				$('#' + 'warehouseCost_automatic_label_' + resultData.warehouseID + '_' + resultData.date).removeClass('invisible');

				// adjust & show shipping hint
				$('#' + 'warehouseCost_automatic_tooltip_' + resultData.warehouseID + '_' + resultData.date).removeClass('invisible').prop('title', 'Prozentwert wurde bereinigt um geschätzte ' + resultData.value.percentageShippingRevenueCleared.shipping + ' € Versandkosteneinnahmen');
			}
			returnValue = parseFloat(returnValue);
			return isNaN(returnValue) ? 'error' : returnValue.toFixed(2);

		}
	};

	// prepare col model
	colModel = [{
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
					$(event.target).apiUpdate('../api/generalCost', 'float', processFunctions.preProcess, processFunctions.postProcess);
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

	/*global warehouses */
	$.each(warehouses, function(index, warehouse) {
		colModel.push({
			display : 'Transp./Lager<br>' + warehouse.name,
			name : 'warehouseCost_manual_' + warehouse.id,
			align : 'right',
			width : 110,
			process : function(celDiv, id) {
				if (id !== 'Average') {
					$(celDiv).insertInput('warehouseCost_manual_' + warehouse.id + '_' + id, '€', function(event) {
						$(event.target).apiUpdate('../api/generalCost', 'float', processFunctions.preProcess, processFunctions.postProcess);
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
				var percentageData;

				$(celDiv).addClass('notModifyable');
				if ($(celDiv).text().trim() !== 'null') {
					percentageData = $.parseJSON($(celDiv).html());

					$(celDiv).html($('<span/>', {
						id : 'warehouseCost_automatic_' + warehouse.id + '_' + id,
						'class' : 'automatic_value',
						html : percentageData.percentage
					})).append($('<label/>', {
						id : 'warehouseCost_automatic_label_' + warehouse.id + '_' + id,
						'class' : 'variableUnit',
						html : '%'
					})).append($('<span/>', {
						id : 'warehouseCost_automatic_tooltip_' + warehouse.id + '_' + id,
						html : '',
						'class' : 'ui-icon ui-icon-help',
						style : 'display: inline-block',
						href : '#',
						'title' : 'Prozentwert wurde bereinigt um geschätzte ' + percentageData.shipping + ' € Versandkosteneinnahmen'
					})).tooltip();
				} else {
					$(celDiv).html($('<span/>', {
						id : 'warehouseCost_automatic_' + warehouse.id + '_' + id,
						'class' : 'automatic_value'
					})).append($('<label/>', {
						id : 'warehouseCost_automatic_label_' + warehouse.id + '_' + id,
						'class' : 'variableUnit invisible',
						html : '%'
					})).append($('<span/>', {
						id : 'warehouseCost_automatic_tooltip_' + warehouse.id + '_' + id,
						'class' : 'ui-icon ui-icon-help invisible',
						style : 'display: inline-block',
						href : '#'
					})).tooltip();
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

function prepareRunningCosts() {'use strict';
	var draggableOptions = {
		containment : ".warehouseGrouping_AssociationContainment",
		scroll : false,
		cursor : "move",
		revert : true
	}, groupData, warehouseData, standardGroupID;

	function dropWarehouse(event, ui) {
		// if source and destination are the same ...
		if ($(event.target)[0] === ui.draggable.parent()[0]) {
			// ... then skip dropping (will revert automatically)
			return;
		}

		var warehouse, groupIDMatches, warehouseID = parseInt(ui.draggable.attr('id').replace(/^warehouseID_(\d+)$/, '$1'), 10);

		// identify warehouse data
		$.each(warehouseData, function(index, currentWarehouse) {
			if (currentWarehouse.id === warehouseID) {
				warehouse = currentWarehouse;
			}
		});

		// if destination valid ...
		if (( groupIDMatches = $(event.target).parent().attr('id').match(/^warehouseGrouping_GroupAssociation_Group_(\d+|NoGroup)$/)) !== null) {
			// ... and not 'NoGroup'
			if (groupIDMatches[1] !== 'NoGroup') {
				// ... perform group change
				$(event.target).apiUpdate('../api', 'int', function(element, type) {
					// remove old element
					ui.draggable.remove();

					return {
						key : 'warehouseGrouping/warehouseToGroup/' + warehouse.id,
						value : groupIDMatches[1]
					};
				}, function(element, type, requestData, resultData) {
					warehouse.groupID = parseInt(groupIDMatches[1], 10);

					// create identical element in new list
					$(event.target).append($('<li/>', {
						id : 'warehouseID_' + warehouse.id,
						html : warehouse.name
					}).draggable(draggableOptions));
				});
			} else {
				// ... otherwise perform delete action
				$(event.target).apiUpdate('../api', 'int', function(element, type) {
					warehouse.groupID = null;

					// remove old element
					ui.draggable.remove();

					// create identical element in new list
					$(event.target).append($('<li/>', {
						id : 'warehouseID_' + warehouse.id,
						html : warehouse.name
					}).draggable(draggableOptions));

					return {
						key : 'warehouseGrouping/warehouseToGroup/delete',
						value : warehouse.id
					};
				}, function(element, type, requestData, resultData) {
				});
			}
		} else {
			// ... otherwise log error
			console.log('Target id doesn\'t match required pattern!');
			return;
		}
	}

	function getGroupListElement(group, standardGroupID) {
		return $('<li/>', {
			id : 'warehouseGrouping_GroupList_Group_' + group.id
		}).append(
		// ... the group name
		$('<span/>', {
			'class' : 'groupName',
			html : group.name
		})).append(
		// ... and a buttonsset, which contains ...
		$('<div/>', {
			'class' : 'groupConfigButtonSet'
		}).append(
		// ... a marking button ...
		$('<a/>', {
			'class' : 'buttonMarkStandardGroup' + (standardGroupID === group.id ? ' standardGroup' : ''),
			html : 'Als Standardgruppe markieren'
		}).button({
			text : false,
			icons : {
				primary : "ui-icon-star"
			}
		}).tooltip({
			position : {
				my : "left center",
				at : "right+220% center"
			},
			show : {
				delay : 500
			}
		}).click(function(event) {
			$(this).apiUpdate('../api', 'int', function(element, type) {
				return {
					key : 'config/warehouseGrouping/standardGroup',
					value : group.id
				};
			}, function(element, type, requestData, resultData) {
				if (resultData.standardGroup === group.id) {
					$('.standardGroup').removeClass('standardGroup');
					$(event.currentTarget).addClass('standardGroup');
				} else {
					prepareRunningCosts();
				}
			});
		})).append(
		// ... a renaming button ...
		$('<a/>', {
			'class' : 'buttonRenameGroup',
			html : 'Gruppe umbenennen'
		}).button({
			text : false,
			icons : {
				primary : "ui-icon-pencil"
			}
		}).tooltip({
			position : {
				my : "left center",
				at : "right+120% center"
			},
			show : {
				delay : 500
			}
		}).click(function(event) {
			$('<div/>').append($('<p/>', {
				html : 'Umbenennen von Gruppe ' + group.id + ':'
			})).append($('<input/>', {
				value : group.name,
				on : {
					change : function(eventObject) {
						group.name = $(eventObject.target).val();
					}
				}
			})).dialog({
				buttons : [{
					text : 'Ok',
					click : function() {
						$(this).apiUpdate('../api', 'int', function(element, type) {
							return {
								key : 'warehouseGrouping/' + group.id,
								value : group.name
							};
						}, function(element, type, requestData, resultData) {
							$('#warehouseGrouping_GroupList_Group_' + group.id + ' .groupName, #warehouseGrouping_GroupAssociation_Group_' + group.id + ' .groupName').html(group.name);
						});
						$(this).dialog("close");
					}
				}, {
					text : 'Abbrechen',
					click : function() {
						$(this).dialog("close");
					}
				}],
				title : 'Gruppe umbenennen',
				modal : true
			});
		})).append(
		// ... and a delete button
		$('<a/>', {
			'class' : 'buttonDeleteGroup',
			html : 'Gruppe löschen'
		}).button({
			text : false,
			icons : {
				primary : "ui-icon-trash"
			}
		}).tooltip({
			position : {
				my : "left center",
				at : "right+20% center"
			},
			show : {
				delay : 500
			}
		}).click(function(event) {
			$('<div/>').append($('<p/>', {
				html : 'Gruppe ' + group.name + ' wirklich löschen?'
			})).dialog({
				buttons : [{
					text : 'Ja',
					click : function() {
						$(this).apiUpdate('../api', 'int', function(element, type) {
							return {
								key : 'warehouseGrouping/delete',
								value : group.id
							};
						}, function(element, type, requestData, resultData) {
							$('#warehouseGrouping_GroupList_Group_' + group.id + ', #warehouseGrouping_GroupAssociation_Group_' + group.id).remove();

							// move associated warehouses to noGroup
							$.each(warehouseData, function(index, warehouse) {
								if (warehouse.groupID === group.id) {
									warehouse.groupID = null;
									$('#warehouseID_' + warehouse.id).remove();

									// create identical element in new list
									$('#warehouseGrouping_GroupAssociation_Group_NoGroup ul').append($('<li/>', {
										id : 'warehouseID_' + warehouse.id,
										html : warehouse.name
									}).draggable(draggableOptions));
								}
							});
						});
						$(this).dialog("close");
					}
				}, {
					text : 'Nein',
					click : function() {
						$(this).dialog("close");
					}
				}],
				dialogClass : 'ui-state-error',
				title : 'Gruppe löschen',
				modal : true
			});
		})).buttonset());
	}

	function getGroupAssociationElement(group) {
		return $('<li/>', {
			id : 'warehouseGrouping_GroupAssociation_Group_' + group.id
		}).append($('<h3/>', {
			'class' : 'ui-widget-header groupName',
			html : group.name
		})).append($('<ul/>', {
			'class' : 'ui-widget-content'
		}).droppable({
			hoverClass : 'ui-state-hover',
			drop : dropWarehouse
		}));
	}

	function generateColModel(groupData) {
		var colModel = [{
			display : 'Monat',
			name : 'month'
		}, {
			display : 'Allg. Betriebskosten',
			name : 'generalCosts',
			width : 100,
			process : function(cell, month) {
				var data = $.parseJSON(cell.innerHTML);

				if (data.isAverage) {
					// do average things ...
					$(cell).addClass('noTableCell').html($('<span/>', {
						html : data.average
					})).append($('<label/>', {
						'class' : 'variableUnit',
						html : '%'
					}));
				} else {
					$(cell).addClass('noTableCell').insertInput('generalCosts_' + month, '%', function(event) {
						$(event.target).apiUpdate('../api/generalCosts', 'percent', function(element, type) {
							var id, dateMatch;

							element.checkFloatval();
							if (type !== 'percent' || isNaN(element.val())) {
								return 'incorrect';
							}

							id = element.attr('id');
							if (( dateMatch = id.match(/generalCosts_(\d{8})/)) !== null) {
								return {
									key : dateMatch[1],
									value : (element.val() / 100).toFixed(4)
								};
							}

							return 'incorrect';
						}, function(element, type, requestData, resultData) {
							data.relativeCosts = resultData.value;
							return (data.relativeCosts ? (data.relativeCosts * 100).toFixed(2) : '');
						});
					}, (data.relativeCosts ? (data.relativeCosts * 100).toFixed(2) : ''));
				}
			}
		}];

		$.each(groupData, function(index, group) {
			colModel.push({
				display : group.name,
				name : 'groupID_' + group.id,
				align : 'center',
				width : 180,
				process : function(cell, month) {
					var data = $.parseJSON(cell.innerHTML), percentSpan;
					if (data.isAverage) {
						// do average things ...
					} else {
						// make table ...
						$(cell).addClass('table')
						// ... add row ...
						.html($('<div/>', {
							'class' : 'tableRow'
						})
						// ... add second cell: display percentage
						.append($('<div/>', {
							id : 'runningCosts_' + month + '_' + group.id + '_percentage',
							'class' : 'tableCell',
							css : {
								'visibility' : (data.absoluteCosts ? 'visible' : 'hidden')
							}
						}).append( percentSpan = $('<span/>', {
							// fill percentage field with: (costs - shippingRevenue) / nettoRevenue
							html : ( data ? (100 * (data.absoluteCosts - data.shippingRevenue) / data.nettoRevenue).toFixed(2) : ''),
							on : {
								change : function(event) {
									$(this).html(( data ? (100 * (data.absoluteCosts - data.shippingRevenue) / data.nettoRevenue).toFixed(2) : '')).parent().css('visibility', (data.absoluteCosts ? 'visible' : 'hidden'));
								}
							}
						})).append($('<label/>', {
							'class' : 'variableUnit',
							html : '%'
						})).append($('<span/>', {
							'class' : 'ui-icon ui-icon-help',
							style : 'display: inline-block',
							'title' : 'Prozentwert wurde um geschätzte ' + data.shippingRevenue.toFixed(2) + ' € Versandkosteneinnahmen bereinigt'
						})).tooltip({
							position : {
								my : "left center",
								at : "right center"
							},
							show : {
								delay : 500
							}
						}))
						// ... add first cell: input field
						.prepend($('<div/>', {
							id : 'runningCosts_' + month + '_' + group.id + '_absolute',
							'class' : 'tableCell'
						}).insertInput('runningCosts_' + month + '_' + group.id, '€', function(event) {
							$(event.target).apiUpdate('../api/runningCosts', 'float', function(element, type) {
								var id, dateGroupMatch;

								element.checkFloatval();
								if (type !== 'float' || isNaN(element.val())) {
									return 'incorrect';
								}

								id = element.attr('id');
								if (( dateGroupMatch = id.match(/runningCosts_(\d{8})_(\d+)/)) !== null) {
									return {
										key : dateGroupMatch[2] + '/' + dateGroupMatch[1],
										value : element.val()
									};
								}

								return 'incorrect';
							}, function(element, type, requestData, resultData) {
								data.absoluteCosts = resultData.value;
								percentSpan.change();
								return (data.absoluteCosts ? data.absoluteCosts.toFixed(2) : '');
							});
						}, (data.absoluteCosts ? data.absoluteCosts.toFixed(2) : ''))));
					}
				}
			});
		});

		return colModel;
	}

	// start asnycronous requests ...
	$.when($.get('../api/warehouseGrouping', function(result, textStatus, jqXHR) {
		groupData = result.data.groupData;
		standardGroupID = result.data.standardGroupID;
	}, 'json'), $.get('../api/warehouseGrouping/warehouses', function(result, textStatus, jqXHR) {
		warehouseData = result.data;
	}, 'json'))
	// ... after successful arival of all necessary data begin building gui
	.then(function() {
		var warehouseGroupingGroupList, warehouseGroupingGroupAssociation, warehouseGroupingWarehouseListUl, warehouseGroupingWarehouseList;

		warehouseGroupingGroupList = $('.warehouseGrouping_GroupList').empty();
		warehouseGroupingGroupAssociation = $('.warehouseGrouping_GroupAssociation').empty();
		warehouseGroupingWarehouseList = $('.warehouseGrouping_WarehouseList').show();
		warehouseGroupingWarehouseListUl = $('ul', warehouseGroupingWarehouseList).empty().droppable({
			hoverClass : 'ui-state-hover',
			drop : dropWarehouse
		});

		// for each group ...
		$.each(groupData, function(index, group) {

			// ... add a list item, which contains ...
			warehouseGroupingGroupList.append(getGroupListElement(group, standardGroupID));

			warehouseGroupingGroupAssociation.append(getGroupAssociationElement(group));
		});

		warehouseGroupingGroupList.append($('<li/>').append($('<span/>', {
			'class' : 'groupName'
		}).append($('<input/>', {
			value : 'Neue Gruppe erstellen'
		}).change(function(input) {
			$(this).apiUpdate('../api', 'string', function(element, type) {
				return {
					key : 'warehouseGrouping',
					value : element.val().replace(' ', '_')
				};
			}, function(element, type, requestData, resultData) {
				prepareRunningCosts();
			});
		}))));

		// place warehouses
		$.each(warehouseData, function(index, warehouse) {
			if (warehouse.groupID === null) {
				$(warehouseGroupingWarehouseListUl).append($('<li/>', {
					html : warehouse.name,
					id : 'warehouseID_' + warehouse.id
				}).draggable(draggableOptions));
			} else {
				$('#warehouseGrouping_GroupAssociation_Group_' + warehouse.groupID + ' ul', warehouseGroupingGroupAssociation).append($('<li/>', {
					html : warehouse.name,
					id : 'warehouseID_' + warehouse.id
				}).draggable(draggableOptions));
			}
		});

		// create table
		$('#runningCostConfigurationNew').flexigrid({
			url : 'runningCost-post-xml-new.php',
			dataType : 'xml',
			colModel : generateColModel(groupData),
			height : 'auto',
			singleSelect : true,
			striped : false,
			title : 'Betriebskosten'
		});
	});
}

$(function() {'use strict';
	var panelStatus = [{
		id : 'amazonCalculation',
		isInitialized : false,
		initialize : prepareAmazon
	}, {
		id : 'reorderStockCalculation',
		isInitialized : false,
		initialize : prepareStock
	}, {
		id : 'generalCostConfigurationOld',
		isInitialized : false,
		initialize : prepareGeneralCostConfig
	}, {
		id : 'generalCostConfiguration',
		isInitialized : false,
		initialize : prepareRunningCosts
	}];

	function initPanel(panel) {
		$.each(panelStatus, function(index, currentPanel) {
			if (currentPanel.id === $(panel).attr('id') && !currentPanel.isInitialized) {
				currentPanel.initialize();
				currentPanel.isInitialized = true;
			}
		});
	}


	$('.config').accordion({
		active : false,
		collapsible : true,
		heightStyle : 'content'
	});

	$('.accordion').accordion({
		collapsible : false,
		heightStyle : 'content'
	});

	$('#tabs').tabs({
		heightStyle : 'content',
		activate : function(event, ui) {
			var scrollTop = $(document).scrollTop();

			initPanel(ui.newPanel);

			window.location.href = ui.newTab.context.hash;
			$(document).scrollTop(scrollTop);
		},
		create : function(event, ui) {
			initPanel(ui.panel);
		}
	});
});
