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
	$(function() {
		$('.groupConfigButtonSet').buttonset().children('.buttonMarkStandardGroup').button({
			text : false,
			icons : {
				primary : "ui-icon-star"
			}
		}).tooltip({
			position : {
				my : "left center",
				at : "right+120% center"
			}
		});
		$('.buttonDeleteGroup').button({
			text : false,
			icons : {
				primary : "ui-icon-trash"
			}
		}).tooltip({
			position : {
				my : "left center",
				at : "right+20% center"
			}
		});
	}); 
</script>
<div class='config'>
	<h3>Konfiguration Lagergruppierung</h3>
	<div class='accordion'>
		<h2>Lagergruppenerstellung</h2>
		<div>
			<h3>Aktuelle Gruppen:</h3>
			<ul class='warehouseGrouping_GroupList'>
				{foreach $warehouseGroups.groupData as $group}
				<li id='warehouseGroupingGroup_{$group.id}'>
					<span class='groupName'>
						<input value='{$group.name}'>
					</span>
					<div class='groupConfigButtonSet'>
						<a class='buttonMarkStandardGroup{if $warehouseGroups.standardGroupID == $group.id} standardGroup{/if}'>Als Standardgruppe markieren</a>
						<a class='buttonDeleteGroup'>Gruppe löschen</a>
					</div>
				</li>
				{/foreach}
				<li>
					<span class='groupName'>
						<input value='Neue Gruppe erstellen'>
					</span>
				</li>
			</ul>
		</div>
		<h2>Lagergruppenzuordnung</h2>
		<div>
			warehouse to warehousegroup will be configured here...
		</div>
	</div>
</div>
{/nocache}