{nocache}
<style>
</style>
<script>
	function initTable() {
		'use strict';
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

					$(cell).addClass('noTableCell').insertInput('generalCosts_' + month, '%', function(event) {
						$(event.target).apiUpdate('../api/generalCosts', 'percent', function(element, type) {
							var id, dateMatch;

							element.checkFloatval();
							if (type !== 'percent' || isNaN(element.val())) {
								return 'incorrect';
							}

							id = element.attr('id');
							if (( dateMatch = id.match(/generalCosts_(\d{ldelim}8{rdelim})/)) !== null) {
								return {
									key : dateMatch[1],
									value : (element.val() / 100).toFixed(4)
								};
							}

							return 'incorrect';
						}, function(element, type, requestData, resultData) {
							data.relativeCosts = resultData.value;
							return (data.relativeCosts * 100).toFixed(2);
						});
					}, ( data ? (data.relativeCosts * 100).toFixed(2) : ''));
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
								if (( dateGroupMatch = id.match(/runningCosts_(\d{ldelim}8{rdelim})_(\d+)/)) !== null) {
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
				});
			});

			return colModel;
		}


		$.when($.get('../api/warehouseGrouping', 'json')).then(function(warehouseGroupingResult) {
			if (warehouseGroupingResult.success) {
				// create table
				$('#runningCostConfigurationNew').flexigrid({
					url : 'runningCost-post-xml-new.php',
					dataType : 'xml',
					colModel : generateColModel(warehouseGroupingResult.data.groupData),
					height : 'auto',
					singleSelect : true,
					striped : false,
					title : 'Betriebskosten'
				});
			} else {
				console.log("error: ", warehouseGroupingResult.error);
			}
		});
	};

	$(initTable); 
</script>
<div class='config'>
	<h3>Konfiguration Lagergruppierung</h3>
	<div class='accordion'>
		<h2>Lagergruppenerstellung</h2>
		<div>
			<ul class='warehouseGrouping_GroupList'>
				<!-- --->
			</ul>
		</div>
		<h2>Lagergruppenzuordnung</h2>
		<div>
			<div class='tableWrapper'>
				<div class='warehouseGrouping_AssociationContainment'>
					<ul class='warehouseGrouping_GroupAssociation'>
						<!-- --->
					</ul>
					<div class='warehouseGrouping_WarehouseList' id='warehouseGrouping_GroupAssociation_Group_NoGroup' style="display:none">
						<h3 class='ui-widget-header'>Nicht zugeordnete Lager</h3>
						<ul class='ui-widget-content' >
							<!-- --->
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<table id='runningCostConfigurationNew' style='display:none'>
	<!-- -->
</table>
{/nocache}