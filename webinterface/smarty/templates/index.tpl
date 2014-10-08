<!DOCTYPE HTML>
<html>
	<head>
		<meta charset='utf-8'>
		<title>Net-Xpress, Plenty-Soap GUI</title>
		<link rel='stylesheet' type='text/css' href='css/style.css'/>
		<link rel='stylesheet' type='text/css' href='css/flexigrid.pack.css'/>
		<link rel='stylesheet' type='text/css' href='//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css'/>
		<!--
			<script src='http://code.jquery.com/jquery-1.8.3.min.js'></script>
			preferr newer jQuery version over 1.8.3 which was used in flexigrid examples
		-->
		<script src='http://code.jquery.com/jquery-2.1.1.min.js'></script>
		<script src='http://code.jquery.com/ui/1.11.1/jquery-ui.js'></script>
		<script src='js/flexigrid.js'></script>
		<script src='js/apiAdapter.js'></script>
		<script src='js/js.js'></script>
	</head>
	<body>
		<div id='tabs'>
			<ul>
				<li>
					<a href='#reorderStockCalculation'>Bestandsautomatik</a>
				</li>
				<li>
					<a href='#amazonCalculation'>Kalkulation Amazon</a>
				</li>
				<li>
					<a href='#generalCostConfiguration'>Konfiguration Kosten</a>
				</li>
			</ul>
			<div id='dialog'>
				<p>
					<span id='dialogIcon'><!-- --></span>
					<span id='dialogText'><!-- --></span>
				</p>
			</div>
			<div id='amazonCalculation'>
				{include file='amazon_config.tpl'}
				<table id='amazonTable' style='display:none'>
					<!-- -->
				</table>
			</div>
			<div id='reorderStockCalculation'>
				{include file='stock_config.tpl'}
				<table id='stockTable' style='display:none'>
					<!-- -->
				</table>
			</div>
			<div id='generalCostConfiguration'>
				{include file='warehouse_grouping_config.tpl'}
				<table id='runningCostConfigurationNew' style='display:none'>
					<!-- -->
				</table>
			</div>
		</div>
		<div class="modal">
			<!-- -->
		</div>
	</body>
</html>
