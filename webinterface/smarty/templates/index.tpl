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
			<!-- display error and debug messages -->
		</div>
		<div>
			<ul id='variableManipulation'>
				<li>
					<label for='calculationTimeSingleWeighted'> Zeitraum zur Berechnung (einfach gewichtet): </label>
					<input id='calculationTimeSingleWeighted' />
					<label class="variableUnit" for='calculationTimeSingleWeighted'>Tage</label>
				<li>
					<label for='calcualtionTimeDoubleWeighted'> Zeitraum zur Berechnung (doppelt gewichtet): </label>
					<input id='calcualtionTimeDoubleWeighted'/>
					<label class="variableUnit" for='calcualtionTimeDoubleWeighted'>Tage</label>
				<li>
					<label for='standardDeviationFaktor'> Faktor Standardabweichung: </label>
					<input id='standardDeviationFaktor'/>
				<li>
					<label for='minimumToleratedSpikes'> Mindestanzahl Spitzen: </label>
					<input id='minimumToleratedSpikes'/>
					<label class="variableUnit" for='minimumToleratedSpikes'>Stück</label>
				<li>
					<label for='spikeTolerance'> Spitzentoleranz: </label>
					<input id='spikeTolerance'/>
					<label class="variableUnit" for='spikeTolerance'>%</label>
			</ul>
			<select id='warehouseSelection'>
				<option>Lagerort auswählen</option>
				{foreach from=$warehouseList item=warehouse}
				<option value='{$warehouse.id}'>{$warehouse.name}</option>
				{/foreach}
			</select>
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
				{else} <td>{$col}
				{/if}
				{/if}
				{/foreach}
				{/foreach}
		</table>
		{include file="pagination.tpl"}
	</body>
</html>
