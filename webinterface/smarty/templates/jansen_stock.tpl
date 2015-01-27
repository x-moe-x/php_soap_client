<style>
	#jansenStatic li {
		display: inline-block;
		margin-right: 1%;
	}

	#jansenStatic li {
		display: inline-block;
		width: 22%;
		margin-right: 1%;
	}

	#jansenStatic li label, #jansenStatic li span {
		display: block;
	}

	#jansenStatic li span {
		text-align: right;
		background-color: #eee;
	}

	.exactMatch {
		background-color: #9f9 !important;
	}

	.erow .exactMatch {
		background-color: #7f7 !important;
	}

	.approximateMatch {
		background-color: #fa5 !important;
	}

	.erow .approximateMatch {
		background-color: #fa3 !important;
	}

	.noMatch {
		background-color: #f77 !important;
	}

	.erow .noMatch {
		background-color: #f99 !important;
	}

	.filter.jansenFilter input {
		margin-top: -2px !important;
	}

	.filter.jansenFilter  label {
		width: auto !important;
	}

	.jansenMatch_label {
		padding: 0 1em;
	}

	.jansenMatch_label_matched {
		background-color: #9f9;
	}

	.jansenMatch_label_unmatched {
		background-color: #f77;
	}

	.jansenMatch_label_partiallyMatched {
		background-color: #fa5;
	}

	.flexigrid div.mDiv .stitle {
		font-weight: normal;
	}
</style>
<script>
	$(function() {
		$('#jansenStockTable').flexigrid({
			url : 'jansenStock-post-xml.php',
			dataType : 'xml',
			colModel : [{
				display : 'Jansen EAN',
				name : 'EAN',
				sortable : true
			}, {
				display : 'Jansen Artikel ID',
				name : 'ExternalItemID',
				width : 130,
				sortable : true
			}, {
				display : 'net-xpress ItemID',
				name : 'ItemID',
				width : 50,
				align : 'center',
				sortable : true
			}, {
				display : 'net-xpress Name',
				name : 'Name',
				width : 500,
				sortable : true
			}, {
				display : 'Jansen Bestand',
				name : 'PhysicalStock',
				width : 50,
				align : 'right',
				sortable : true
			}, {
				display : 'letzte Aktualisierung',
				name : 'Timestamp',
				width : 120,
				sortable : true
			}, {
				display : 'Data',
				name : 'Data',
				hide : true,
				process : function(cellDiv, EAN) {
					var data = $.parseJSON($(cellDiv).html()), newClass;

					if (data.match && data.exactMatch) {
						newClass = 'exactMatch';
					} else if (data.match) {
						newClass = 'approximateMatch';
					} else {
						newClass = 'noMatch';
					}

					$('#row' + EAN + ' > td').addClass(newClass);
				}
			}],
			searchitems : [{
				display : 'Jansen EAN',
				name : 'EAN'
			}, {
				display : 'Jansen Artikel Nr.',
				name : 'ExternalItemID'
			}, {
				display : 'net-xpress Item ID',
				name : 'ItemID'
			}, {
				display : 'net-xpress Name',
				name : 'Name'
			}],
			buttons : [{
				name : 'Filter',
				bclass : 'filter jansenFilter'
			}],
			matches : [{
				'key' : 'unmatched',
				'value' : 'Keine Zuordnung'
			}, {
				'key' : 'partiallyMatched',
				'value' : 'Partielle Zuordnung'
			}, {
				'key' : 'matched',
				'value' : 'Vollständige Zuordnung'
			}],
			params : [{
				name : 'filterJansenMatch',
				value : '1,2'
			}],
			onSuccess : function(g) {
				var matches = this.matches, params = this.params;
				if ($('.jansenFilter input', g.tDiv).length === 0) {

					// change formatting
					$('.jansenFilter', g.tDiv).css({
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
						var filters = [];

						$.each(matches, function(index, match) {
							filters.push($('<div/>')
							// insert input ...
							.append($('<input/>', {
								id : 'jansenMatch_' + match.key,
								type : 'checkbox',
								checked : params[0].value.indexOf(index) !== -1,
								on : {
									change : function(event) {
										var filterJansenMatch = [];

										// collect all checked marking filters
										$.each(matches, function(innerIndex, innerMatch) {
											if ($('#jansenMatch_' + innerMatch.key).is(':checked')) {
												filterJansenMatch.push(innerIndex);
											}
										});

										// adjust params
										params[0].value = filterJansenMatch.join();

										// update grid
										g.populate();
									}
								}
							}),
							// ... and label
							$('<label/>', {
								'for' : 'jansenMatch_' + match.key,
								html : match.value,
								'class' : 'jansenMatch_label_' + match.key + ' jansenMatch_label',
							})));
						});

						return $('<div/>', {
							'class' : 'customButtonContent'
						}).append(filters);
					});
				}

				if ($('.jansenSubtitle', g.mDiv).length === 0) {
					$(g.mDiv).append($('<div/>', {
						'class' : 'jansenSubtitle stitle',
						html : this.subtitle
					}));
				}
			},
			height : 'auto',
			singleSelect : true,
			striped : true,
			sortname : "EAN",
			sortorder : "asc",
			usepager : true,
			useRp : true,
			height : 500,
			rp : 20,
			rpOptions : [10, 20, 30, 50, 100, 200],
			title : 'Bestand: Jansen',
			subtitle : 'Zuordnung: Jansen-EAN -> net-xpress-EAN2',
			pagetext : 'Seite',
			outof : 'von',
			procmsg : 'Bitte warten...'
		});
	});
</script>
<div class='config'>
	<h3>Informationen Jansenbestand</h3>
	<div class='accordion'>
		<h3>Jansen Update</h3>
		<div>
			<ul id='jansenStatic'>
				<li>
					<label>Letzte Dateiaktualisierung von Jansen</label>
					<span>{$jansenLastUpdate|date_format:"%d.%m.%Y, %H:%M:%S"} Uhr</span>
				</li>
			</ul>
		</div>
	</div>
</div>
