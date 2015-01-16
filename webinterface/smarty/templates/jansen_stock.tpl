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
				name : 'NxItemID',
				sortable : true
			}, {
				display : 'net-xpress Name',
				name : 'Name',
				sortable : true
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