<div class='config'>
	<h3>Konfiguration Amazonkalkulation</h3>
	<div class='accordion'>
		<h3>Übersicht</h3>
		<div>
			<ul id='amazonStatic'>
				<li>
					<label>Name</label>
					<span>{$amazonData.Name}</span>
				</li>
				<li>
					<label>Herkunft ID</label>
					<span>{$amazonData.SalesOrderReferrerID}</span>
				</li>
				<li>
					<label>Preis</label>
					<span>{if $amazonData.PriceColumn == 0}Standard{else}Preis {$amazonData.PriceColumn}{/if}</span>
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
					<label>Provision / Kosten</label>
					<input value='XX_17,85'/>
					<label class='variableUnit'>%</label>
				</li>
				<li>
					<label>Min.-Marge</label>
					<input value='XX_5'/>
					<label class='variableUnit'>%</label>
				</li>
				<li>
					<label>Zeitraum Trendmessung</label>
					<input value='XX_30'/>
					<label class='variableUnit'>Tage</label>
				</li>
			</ul>

		</div>
	</div>
</div>