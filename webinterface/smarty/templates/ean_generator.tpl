<div class='config'>
	<h3>EAN Konfiguration</h3>
	<div>
		<ul class='fieldList'>
			<li>
				<label for='#baseEan'>Grund-EAN</label>
				<input id='baseEan' type="text" value="xxx" />
			</li>
			<li>
				<label for='#maxNumberOfEan'>Anzahl EAN</label>
				<input id='maxNumberOfEan' type="text" value="xxx" />
			</li>
		</ul>
	</div>
</div>
<div id="eanTabs" style='display:none'>
	<ul>
		<li>
			<a href="#generateEan">EAN generieren</a>
		</li>
		<li>
			<a href="#checkEan">EAN überprüfen</a>
		</li>
	</ul>
	<div id="generateEan">
		<ul class='fieldList'>
			<li>
				<label for='#itemId'>ItemID</label>
				<input id='itemId' type="text" />
			</li>
			<li>
				<label for='#generatedEan'>EAN</label>
				<input id='generatedEan' type="text"/>
			</li>
		</ul>
	</div>
	<div id="checkEan">
		<ul class='fieldList'>
			<li>
				<label for='#eanCheckField'>EAN</label>
				<input id='eanCheckField' type="text" />
				<label for='#eanCheckField' id='eanValidity' class='ui-state-default ui-corner-all'> <span class="ui-icon ui-icon-circle-close"> </span> </label>
			</li>
		</ul>
	</div>
</div>
