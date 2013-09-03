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
		<div>
			<ul id='variableManipulation'>
				<li>
					<label {if $config.CalculationTimeSingleWeighted.Active == 0}class='disabled'{/if} for='calculationTimeSingleWeighted'> Zeitraum zur Berechnung (einfach gewichtet): </label>
					<input id='calculationTimeSingleWeighted' value='{$config.CalculationTimeSingleWeighted.Value}' {if $config.CalculationTimeSingleWeighted.Active == 0}disabled{/if}/>
					<label class='variableUnit' for='calculationTimeSingleWeighted'>Tage</label>
				<li>
					<label {if $config.CalcualtionTimeDoubleWeighted.Active == 0}class='disabled'{/if} for='calcualtionTimeDoubleWeighted'> Zeitraum zur Berechnung (doppelt gewichtet): </label>
					<input id='calcualtionTimeDoubleWeighted' value='{$config.CalcualtionTimeDoubleWeighted.Value}' {if $config.CalcualtionTimeDoubleWeighted.Active == 0}disabled{/if}/>
					<label class='variableUnit' for='calcualtionTimeDoubleWeighted'>Tage</label>
				<li>
					<label {if $config.StandardDeviationFactor.Active == 0}class='disabled'{/if} for='standardDeviationFactor'> Faktor Standardabweichung: </label>
					<input id='standardDeviationFactor' value='{$config.StandardDeviationFactor.Value}' {if $config.StandardDeviationFactor.Active == 0}disabled{/if}/>
				<li>
					<label {if $config.MinimumToleratedSpikes.Active == 0}class='disabled'{/if} for='minimumToleratedSpikes'> Mindestanzahl Spitzen: </label>
					<input id='minimumToleratedSpikes' value='{$config.MinimumToleratedSpikes.Value}' {if $config.MinimumToleratedSpikes.Active == 0}disabled{/if}/>
					<label class='variableUnit' for='minimumToleratedSpikes'>Stück</label>
				<li>
					<label {if $config.SpikeTolerance.Active == 0}class='disabled'{/if} for='spikeTolerance'> Spitzentoleranz: </label>
					<input id='spikeTolerance' value='{$config.SpikeTolerance.Value}'  {if $config.SpikeTolerance.Active == 0}disabled{/if}/>
					<label class='variableUnit' for='spikeTolerance'>%</label>
			</ul>
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
