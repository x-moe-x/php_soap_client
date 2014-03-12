<!DOCTYPE HTML>
<html>
	<head>
		<meta charset='utf-8'>
		<title>Net-Xpress, Plenty-Soap GUI</title>
		<link rel='stylesheet' type='text/css' href='css/style.css'/>
		<link rel='stylesheet' type='text/css' href='css/flexigrid.pack.css'/>
		<link rel='stylesheet' type='text/css' href='//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css'/>
		<script src='http://code.jquery.com/jquery-1.8.3.min.js'></script>
		<script src='http://code.jquery.com/ui/1.10.3/jquery-ui.js'></script>
		<script src='js/flexigrid.js'></script>
		<script src='js/js.js'></script>
	</head>
	<body>
		<div id='errorMessages'>
			{$debug}
		</div>
		<div id='dialog'>
			<p>
				<span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20% 0;"></span>
				<span id='dialogText'></span>
			</p>
		</div>
		<div id='config' class='box'>
			<strong>Konfiguration
			<button id='toggleConfig'>
				+
			</button></strong>

			<div id='fade'>
				<div id='variableManipulation'>
					<ul id='globalManipulation'>
						<li>
							<label {if $config.SpikeTolerance.Active == 0}class='disabled'{/if} for='spikeTolerance'> Spitzentoleranz: </label>
							<input id='spikeTolerance' value='{$config.SpikeTolerance.Value * 100}'  {if $config.SpikeTolerance.Active == 0}disabled{/if}/>
							<label class='variableUnit' for='spikeTolerance'>%</label>
						<li>
							<label {if $config.StandardDeviationFactor.Active == 0}class='disabled'{/if} for='standardDeviationFactor'> Faktor Standardabweichung: </label>
							<input id='standardDeviationFactor' value='{$config.StandardDeviationFactor.Value}' {if $config.StandardDeviationFactor.Active == 0}disabled{/if}/>
					</ul>
					<div id='firstPeriodManipulation' class='box'>
						<strong>Zeitraum A</strong>
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
					<div id='secondPeriodManipulation' class='box'>
						<strong>Zeitraum B</strong>
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
				</div>
				<div id='switches' class='box'>
					<strong>Automatikeinstellungen</strong>
					<label for='calculationActive'>Daten aktualisieren / Kalkulation: </label>
					<select id='calculationActive' {if $config.CalculationActive.Active == 0}disabled{/if}>
						<option {if $config.CalculationActive.Value != 0}selected{/if} value='1'>On</option>
						<option {if $config.CalculationActive.Value == 0}selected{/if} value='0'>Off</option>
					</select>
					<label for='writebackActive'>Rückschreiben: </label>
					<select id='writebackActive' {if $config.WritebackActive.Active == 0}disabled{/if}>
						<option {if $config.WritebackActive.Value != 0}selected{/if} value='1'>On</option>
						<option {if $config.WritebackActive.Value == 0}selected{/if} value='0'>Off</option>
					</select>
				</div>
				<div id='manualOverride' class='box'>
					<strong>Manuelle Funktionsauslösung</strong>
					<ul style='float:left'>
						<li>
							<button id='buttonManualUpdate' class='buttonsLeft'>
								Aktualisierung auslösen
							</button>
						</li>
						<li>
							<button id='buttonManualCalculate'class='buttonsLeft'>
								Kalkulation auslösen
							</button>
						</li>
						<li>
							<button id='buttonManualWriteBack'class='buttonsLeft'>
								Rückschreiben auslösen
							</button>
						</li>
					</ul>
					<ul style='float:right'>
						<li>
							<button id='buttonResetArticles'class='buttonsRight'>
								Reset Artikel-Datenbank
							</button>
						</li>
						<li>
							<button id='buttonResetOrders'class='buttonsRight'>
								Reset Order-Datenbank
							</button>
						</li>
					</ul>
					<div class='clear'></div>
				</div>
				<div id='reorderStock' class='box'>
					<strong>Meldebestandseinkaufswert</strong>
					<ul style='float:left'>
						<li>
							<span class='name'>aktuell</span><span class='value'>{$reorderSums.currentReorderStock|number_format:2:",":"."}</span>
						</li>
						<li>
							<span class='name'>vorgeschlagen</span><span class='value'>{$reorderSums.proposedReorderStock|number_format:2:",":"."}</span>
						</li>
						<li>
							<span class='name'>maximal</span><span class='value'>{$reorderSums.maxStock|number_format:2:",":"."}</span>
						</li>
					</ul>
					<ul style='float:right;margin-top:0.5em;'>
						<li>
							<span class='name'>Differenz</span><span class='value'>{($reorderSums.proposedReorderStock - $reorderSums.currentReorderStock)|number_format:2:",":"."}</span>
						</li>
					</ul>
					<div class='clear'></div>
				</div>
			</div>
		</div>
		<table id='resultTable' style='display:none'>
			<!-- -->
		</table>
		<div class="modal">
			<!-- -->
		</div>
	</body>
</html>
