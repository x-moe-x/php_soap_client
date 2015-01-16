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