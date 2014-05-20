<div class='config'>
	<h3>Konfiguration Amazonkalkulation</h3>
	<div class='accordion'>
		<h3>Übersicht</h3>
		<div>
			<ul id='amazonStatic'>
				<li>
					<label>Name</label>
					<span>{$amazonStatic.Name}</span>
				</li>
				<li>
					<label>Herkunft ID</label>
					<span>{$amazonStatic.SalesOrderReferrerID}</span>
				</li>
				<li>
					<label>Preis</label>
					<span>{if $amazonStatic.PriceColumn == 0}Standard{else}Preis {$amazonStatic.PriceColumn}{/if}</span>
				</li>
				<li>
					<label>anteillige Kosten Herkunft % Transport/Lager (Durchschn. der letzten zwei Werte)</label>
					<span>8,7 %</span>
				</li>
				<li>
					<label>Allg. Betriebskosten % (Durchschn. der ketzten zwei Werte)</label>
					<span>9,82 %</span>
				</li>
			</ul>
		</div>
		<h3>Variablenanpassungen</h3>
		<div>
			<ul id='amazonVariables'>
				<li>
					<label for='provisionCosts' {if ProvisionCosts|array_key_exists:$amazonVariables}{else}class='disabled'{/if}>Provision / Kosten</label>
					<input id='provisionCosts' value='{if ProvisionCosts|array_key_exists:$amazonVariables}{$amazonVariables.ProvisionCosts * 100}{else}{/if}' {if ProvisionCosts|array_key_exists:$amazonVariables}{else}disabled{/if}/>
					<label for='provisionCosts' class='variableUnit'>%</label>
				</li>
				<li>
					<label for='minimumMarge' {if MinimumMarge|array_key_exists:$amazonVariables}{else}class='disabled'{/if}>Min.-Marge</label>
					<input id='minimumMarge' value='{if MinimumMarge|array_key_exists:$amazonVariables}{$amazonVariables.MinimumMarge * 100}{else}{/if}' {if MinimumMarge|array_key_exists:$amazonVariables}{else}disabled{/if}/>
					<label for='minimumMarge' class='variableUnit'>%</label>
				</li>
				<li>
					<label for='measuringTimeFrame' {if MeasuringTimeFrame|array_key_exists:$amazonVariables}{else}class='disabled'{/if}>Zeitraum Trendmessung</label>
					<input id='measuringTimeFrame' value='{if MeasuringTimeFrame|array_key_exists:$amazonVariables}{$amazonVariables.MeasuringTimeFrame}{else}{/if}' {if MeasuringTimeFrame|array_key_exists:$amazonVariables}{else}disabled{/if}/>
					<label for='measuringTimeFrame' class='variableUnit'>Tage</label>
				</li>
			</ul>

		</div>
	</div>
</div>