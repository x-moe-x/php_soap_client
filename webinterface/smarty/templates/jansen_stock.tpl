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
		background-color: #fa9 !important;
	}

	.erow .approximateMatch {
		background-color: #fa7 !important;
	}

	.noMatch {
		background-color: #f77 !important;
	}

	.erow .noMatch {
		background-color: #f99 !important;
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
				sortable : true
			}, {
				display : 'Jansen Bestand',
				name : 'PhysicalStock',
				sortable : true
			}, {
				display : 'net-xpress ItemID',
				name : 'ItemID',
				sortable : true
			}, {
				display : 'net-xpress Name',
				name : 'Name',
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
						$('#row' + EAN + ' > td').addClass('');
					} else {
						newClass = 'noMatch';
					}

					$('#row' + EAN + ' > td').addClass(newClass);
				}
			}],
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
			pagetext : 'Seite',
			outof : 'von',
			procmsg : 'Bitte warten...'
		});
	});
</script>
<div class='config'>
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