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

	.buttonMarkStandardGroup.StandardGroup .ui-icon {
		background-image: url("images/ui-icons_004000_256x240.png");
	}

	.buttonMarkStandardGroup.ui-state-active .ui-icon, .buttonMarkStandardGroup.ui-state-hover .ui-icon {
		background-image: url("images/ui-icons_008000_256x240.png");
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
				at : "right+220% center"
			}
		});
		$('.buttonRenameGroup').button({
			text : false,
			icons : {
				primary : "ui-icon-pencil"
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
				primary : "ui-icon-circle-close"
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
				<li>
					<span class='groupName'>Net-Xpress</span>
					<div class='groupConfigButtonSet'>
						<a class='buttonMarkStandardGroup'>Als Standardgruppe markieren</a>
						<a class='buttonRenameGroup'>Gruppe umbenennen</a>
						<a class='buttonDeleteGroup'>Gruppe löschen</a>
					</div>
				</li>
				<li>
					<span class='groupName'>Jansen</span>
					<div class='groupConfigButtonSet'>
						<a class='buttonMarkStandardGroup'>Als Standardgruppe markieren</a>
						<a class='buttonRenameGroup'>Gruppe umbenennen</a>
						<a class='buttonDeleteGroup'>Gruppe löschen</a>
					</div>
				</li>
				<li>
					<span class='groupName'>Schmidt</span>
					<div class='groupConfigButtonSet'>
						<a class='buttonMarkStandardGroup'>Als Standardgruppe markieren</a>
						<a class='buttonRenameGroup'>Gruppe umbenennen</a>
						<a class='buttonDeleteGroup'>Gruppe löschen</a>
					</div>
				</li>
				<li>
					<span class='groupName'>Plakatshop24</span>
					<div class='groupConfigButtonSet'>
						<a class='buttonMarkStandardGroup'>Als Standardgruppe markieren</a>
						<a class='buttonRenameGroup'>Gruppe umbenennen</a>
						<a class='buttonDeleteGroup'>Gruppe löschen</a>
					</div>
				</li>
				<li>
					<span class='groupName'><input value='Neue Gruppe erstellen'></span>
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