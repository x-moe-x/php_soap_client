<div class='config'>
	<h3>Konfiguration</h3>
	<div class='accordion'>
		<h3 title='Änderungen an dieser Stelle werden erst nach erneuter Kalkulation sichtbar (spätestens bei der automatischen Ausführung)'>Bestandsberechnung</h3>
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
			<div id='firstPeriodManipulation' class='column box leftColumn'>
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
						<label class='variableUnit' for='minimumOrdersA'>Best.</label>
				</ul>
			</div>
			<div id='secondPeriodManipulation' class='column box rightColumn'>
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
						<label class='variableUnit' for='minimumOrdersB'>Best.</label>
				</ul>
			</div>
		</div>
		<h3 title='Hier können verschiedene Stufen der Automatisierung eingestellt werden. Rückschreiben'>Automatikeinstellungen</h3>
		<div id='switches'>
			<label for='calculationActive'>Daten aktualisieren / Kalkulation: </label>
			<select id='calculationActive' {if $config.CalculationActive.Active == 0}disabled{/if}>
				<option {if $config.CalculationActive.Value != 0}selected{/if} value='1'>On</option>
				<option {if $config.CalculationActive.Value == 0}selected{/if} value='0'>Off</option>
			</select>
			<label for='writebackActive'>Rückschreiben: </label>
			<select id='writebackActive' {if ($config.WritebackActive.Active == 0) || ($config.CalculationActive.Value == 0)}disabled{/if}>
				<option {if $config.WritebackActive.Value != 0}selected{/if} value='1'>On</option>
				<option {if $config.WritebackActive.Value == 0}selected{/if} value='0'>Off</option>
			</select>
		</div>
		<h3 title='Diese Einstellungen Beeinflussen die automatische Ausführung der Bestandsberechnung und Datenübermittlung an Plenty'>Manuelle Funktionsauslösung</h3>
		<div id='manualOverride'>
			<ul class='column leftColumn'>
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
			<ul class='column rightColumn'>
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
		</div>
		<h3 title='Hier wird der Gesamtwert des aktuellen sowie des vorgeschlagenen Meldebestandes angezeigt'>Meldebestandseinkaufswert</h3>
		<div id='reorderStock'>
			<ul class='column leftColumn'>
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
			<ul class='column rightColumn'>
				<li>
					<span class='name'>Differenz</span><span class='value'>{($reorderSums.proposedReorderStock - $reorderSums.currentReorderStock)|number_format:2:",":"."}</span>
				</li>
			</ul>
		</div>
		<h3>Debug-Meldungen</h3>
		<div id='errorMessages'>
			{$debug}
		</div>
	</div>
</div>