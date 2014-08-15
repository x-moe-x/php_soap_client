{nocache}
<style>
	.warehouseGrouping_GroupList li {
		margin: 0 0 1em;
	}

	.groupName {
		display: inline-block;
		width: 200px;
	}

	.groupConfigButtonSet {
		display: inline;
	}

	.buttonMarkStandardGroup.standardGroup .ui-icon {
		background-image: url("images/ui-icons_008000_256x240.png");
	}

	.buttonMarkStandardGroup.ui-state-active .ui-icon, .buttonMarkStandardGroup.ui-state-hover .ui-icon {
		background-image: url("images/ui-icons_00a000_256x240.png");
	}

	.buttonDeleteGroup .ui-icon {
		background-image: url("images/ui-icons_990000_256x240.png");
	}

	.buttonDeleteGroup.ui-state-active .ui-icon, .buttonDeleteGroup.ui-state-hover .ui-icon {
		background-image: url("images/ui-icons_ff0000_256x240.png");
	}

	.warehouseGrouping_AssociationContainment .groupName {
		display: block;
		width: auto;
	}

	.warehouseGrouping_AssociationContainment h3 {
		border-radius: 5px 5px 0 0;
		margin: 0;
		padding: 0.2em;
	}

	.warehouseGrouping_AssociationContainment ul {
		border-radius: 0 0 5px 5px;
		margin: 0;
		padding: 1em;
	}

	.warehouseGrouping_WarehouseList {
		margin: 1em 0 0;
		padding: 1em;
	}

	.tableWrapper {
		display: table;
		width: 100%;
		border-spacing: 1em 0;
	}

	.warehouseGrouping_AssociationContainment {
		display: table-row;
	}

	.warehouseGrouping_GroupAssociation, .warehouseGrouping_WarehouseList {
		display: table-cell;
		padding: 0 !important;
		width: 50%;
	}

	.warehouseGrouping_GroupAssociation > li {
		margin: 1em 0 0;
	}

</style>
<script>
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
			'class' : 'buttonMarkStandardGroup' + (standardGroupID == group.id ? ' standardGroup' : ''),
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
					populateWarehouseGroupingGroupList();
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
							alert("Implement deletion of associated warehouses!");							
							return {
								key : 'warehouseGrouping/delete',
								value : group.id
							};
						}, function(element, type, requestData, resultData) {
							$('#warehouseGrouping_GroupList_Group_' + group.id + ', #warehouseGrouping_GroupAssociation_Group_' + group.id).remove();
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

	function populateWarehouseGrouping() {
		'use strict';
		var draggableOptions = {
			containment : ".warehouseGrouping_AssociationContainment",
			scroll : false,
			cursor : "move",
			revert : true
		}, warehouseGroupingWarehouseList = $('.warehouseGrouping_WarehouseList').hide();

		function dropFunction(event, ui) {
			// if source and destination are the same ...
			if ($(event.target)[0] === $(ui.draggable).parent()[0]) {
				// ... then skip dropping (will revert automatically)
				return;
			}

			// store warehouse data
			var warehouse = {
				id : ui.draggable.attr('id'),
				name : ui.draggable.text()
			}, groupIDMatches;

			// if destination valid ...			
			if (groupIDMatches = $(event.target).parent().attr('id').match(/^warehouseGrouping_GroupAssociation_Group_(\d+|NoGroup)$/)) {
				// ... perform group change
				console.log('Target group: ' + groupIDMatches[1]);
			} else {
				// ... otherwise log error
				console.log('Target id doesn\'t match required pattern!');
				return;
			}

			// remove old element
			$(ui.draggable).detach();

			// create identical element in new list
			$(event.target).append($('<li/>', {
				id : warehouse.id,
				html : warehouse.name
			}).draggable(draggableOptions));
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
				drop : dropFunction
			}));
		}


		$.get('../api/warehouseGrouping', function(result, textStatus, jqXHR) {
			var warehouseGroupingGroupList = $('.warehouseGrouping_GroupList').empty();
			var warehouseGroupingGroupAssociation = $('.warehouseGrouping_GroupAssociation').empty();
			var warehouseGroupingWarehouseListUl = $('ul', warehouseGroupingWarehouseList).empty().droppable({
				hoverClass : 'ui-state-hover',
				drop : dropFunction
			});

			// for each group ...
			$.each(result.data.groupData, function(index, group) {

				// ... add a list item, which contains ...
				warehouseGroupingGroupList.append(getGroupListElement(group, result.data.standardGroupID));

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
					populateWarehouseGrouping();
				});
			}))));

			// place warehouses
			$.get('../api/warehouseGrouping/warehouses', function(result, textStatus, jqXHR) {
				$.each(result.data, function(index, warehouse) {
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

				if ($('li', warehouseGroupingWarehouseList).length > 0) {
					$(warehouseGroupingWarehouseList).show();
				}
			});

		}, 'json');
	};

	$(populateWarehouseGrouping);

</script>
<div class='config'>
	<h3>Konfiguration Lagergruppierung</h3>
	<div class='accordion'>
		<h2>Lagergruppenerstellung</h2>
		<div>
			<ul class='warehouseGrouping_GroupList'></ul>
		</div>
		<h2>Lagergruppenzuordnung</h2>
		<div>
			<div class='tableWrapper'>
				<div class='warehouseGrouping_AssociationContainment'>
					<ul class='warehouseGrouping_GroupAssociation'></ul>
					<div class='warehouseGrouping_WarehouseList' id='warehouseGrouping_GroupAssociation_Group_NoGroup' style="display:none">
						<h3 class='ui-widget-header'>Nicht zugeordnete Lager</h3>
						<ul class='ui-widget-content' ></ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
{/nocache}