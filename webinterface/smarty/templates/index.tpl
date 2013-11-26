<!DOCTYPE HTML>
<html>
	<head>
		<meta charset='utf-8'>
		<title>Net-Xpress, Plenty-Soap GUI</title>
		<link rel='stylesheet' type='text/css' href='css/style.css'/>
		<link rel='stylesheet' type='text/css' href='css/flexigrid.pack.css'/>
		<script src='http://code.jquery.com/jquery-1.8.3.min.js'></script>
		<script src='js/flexigrid.pack.js'></script>
		<script src='js/js.js'></script>
	</head>
	<body>
		<div id='errorMessages'>
			{$debug}
		</div>
		<div id='variableManipulation'>
			<h2>Konfiguration</h2>
			<ul id='globalManipulation'>
				<li>
					<label {if $config.SpikeTolerance.Active == 0}class='disabled'{/if} for='spikeTolerance'> Spitzentoleranz: </label>
					<input id='spikeTolerance' value='{$config.SpikeTolerance.Value * 100}'  {if $config.SpikeTolerance.Active == 0}disabled{/if}/>
					<label class='variableUnit' for='spikeTolerance'>%</label>
				<li>
					<label {if $config.StandardDeviationFactor.Active == 0}class='disabled'{/if} for='standardDeviationFactor'> Faktor Standardabweichung: </label>
					<input id='standardDeviationFactor' value='{$config.StandardDeviationFactor.Value}' {if $config.StandardDeviationFactor.Active == 0}disabled{/if}/>
			</ul>
			<div id='firstPeriodManipulation'>
				<h2>Zeitraum A</h2>
				<ul>
					<li>
						<label {if $config.CalculationTimeA.Active == 0}class='disabled'{/if} for='calculationTimeA'> Berechnungszeitraum: </label>
						<input id='calculationTimeA' value='{$config.CalculationTimeA.Value}' {if $config.CalculationTimeA.Active == 0}disabled{/if}/>
						<label class='variableUnit' for='calculationTimeA'>Tage</label>
					<li>
						<label {if $config.MinimumToleratedSpikesA.Active == 0}class='disabled'{/if} for='minimumToleratedSpikesA'> Mindestanzahl Spitzen: </label>
						<input id='minimumToleratedSpikesA' value='{$config.MinimumToleratedSpikesA.Value}' {if $config.MinimumToleratedSpikesA.Active == 0}disabled{/if}/>
						<label class='variableUnit' for='minimumToleratedSpikesA'>Spitzen</label>
					<li>
						<label {if $config.MinimumOrdersA.Active == 0}class='disabled'{/if} for='minimumOrdersA'> Mindestanzahl Bestellungen: </label>
						<input id='minimumOrdersA' value='{$config.MinimumOrdersA.Value}' {if $config.MinimumOrdersA.Active == 0}disabled{/if}/>
						<label class='variableUnit' for='minimumOrdersA'>Bestellungen</label>
				</ul>
			</div>
			<div id='secondPeriodManipulation'>
				<h2>Zeitraum B</h2>
				<ul>
					<li>
						<label {if $config.CalculationTimeB.Active == 0}class='disabled'{/if} for='calculationTimeB'> Berechnungszeitraum: </label>
						<input id='calculationTimeB' value='{$config.CalculationTimeB.Value}' {if $config.CalculationTimeB.Active == 0}disabled{/if}/>
						<label class='variableUnit' for='calculationTimeB'>Tage</label>
					<li>
						<label {if $config.MinimumToleratedSpikesB.Active == 0}class='disabled'{/if} for='minimumToleratedSpikesB'> Mindestanzahl Spitzen: </label>
						<input id='minimumToleratedSpikesB' value='{$config.MinimumToleratedSpikesB.Value}' {if $config.MinimumToleratedSpikesB.Active == 0}disabled{/if}/>
						<label class='variableUnit' for='minimumToleratedSpikesB'>Spitzen</label>
					<li>
						<label {if $config.MinimumOrdersB.Active == 0}class='disabled'{/if} for='minimumOrdersB'> Mindestanzahl Bestellungen: </label>
						<input id='minimumOrdersB' value='{$config.MinimumOrdersB.Value}' {if $config.MinimumOrdersB.Active == 0}disabled{/if}/>
						<label class='variableUnit' for='minimumOrdersB'>Bestellungen</label>
				</ul>
			</div>
			<div class='clear'></div>
			<select id='warehouseSelection' disabled>
				<option>Lagerort auswählen</option>
				{foreach from=$warehouseList item=warehouse}
				<option value='{$warehouse.id}' {if $warehouse.id == 1}selected{/if}>{$warehouse.name}</option>
				{/foreach}
			</select>
		</div>
		<table id='resultTable' style='display:none'></table>
		<div class="modal"></div>
	</body>
</html>
