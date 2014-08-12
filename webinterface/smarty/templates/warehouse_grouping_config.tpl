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
</style>
<script>
	function populateWarehouseGroupingGroupList() {
		$.get('../api/warehouseGrouping', function(result, textStatus, jqXHR) {
			var warehouseGroupingGroupList = $('.warehouseGrouping_GroupList').empty();

			// for each group ...
			$.each(result.data.groupData, function(index, group) {

				// ... add a list item, which contains ...
				warehouseGroupingGroupList.append($('<li/>').append(
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
					'class' : 'buttonMarkStandardGroup' + (result.data.standardGroupID == group.id ? ' standardGroup' : ''),
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
						value : group.name
					})).dialog({
						buttons : [{
							text : 'Ok',
							click : function() {
								$(this).dialog("close");
								alert("Not yet implemented!");
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
								$(this).dialog("close");
								alert("Not yet implemented!");
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
				})).buttonset()));
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
					populateWarehouseGroupingGroupList();
				});
			}))));
		}, 'json');
	};

	$(populateWarehouseGroupingGroupList);

</script>
<div class='config'>
	<h3>Konfiguration Lagergruppierung</h3>
	<div class='accordion'>
		<h2>Lagergruppenerstellung</h2>
		<div>
			<h3>Aktuelle Gruppen:</h3>
			<ul class='warehouseGrouping_GroupList'></ul>
		</div>
		<h2>Lagergruppenzuordnung</h2>
		<div>
			warehouse to warehousegroup will be configured here...
		</div>
	</div>
</div>
{/nocache}