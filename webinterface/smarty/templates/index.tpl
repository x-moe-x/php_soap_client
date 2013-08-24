<!DOCTYPE HTML>
<html>
	<head>
		<meta charset='utf-8'>
		<title>Net-Xpress, Plenty-Soap GUI</title>
		<link rel='stylesheet' type='text/css' href='style.css'/>
		<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
		<script src="js.js"></script>
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
			<input id='initCalculation' type='button' value='Kalkulation manuell auslösen' />
		</div>
		{include file="pagination.tpl"}
		<div id='filterSelection'>
			Filter: Alle anzeigen
		</div>
		<table id='resultTable'>
			{foreach from=$rows item=row name=rows}
			{if $smarty.foreach.rows.index == 0}
			<tr>
				{elseif $smarty.foreach.rows.index is even}
			<tr class='articleTableEven'>
				{else}
			<tr class='articleTableOdd'>
				{/if}
				{foreach from=$row item=col name=cols}
				{if $smarty.foreach.rows.index == 0}
				<th>{$col}

				{else}
				{if $smarty.foreach.cols.index == 4} <td class="markingColumn marking{$col}">●
				{elseif ($smarty.foreach.cols.index == 2)||($smarty.foreach.cols.index == 3)||($smarty.foreach.cols.index == 5)} <td class='right'>{$col}
				{else} <td>{$col}
				{/if}
				{/if}
				{/foreach}
				{/foreach}
		</table>
		{include file="pagination.tpl"}
		<div class="modal"></div>
	</body>
</html>
