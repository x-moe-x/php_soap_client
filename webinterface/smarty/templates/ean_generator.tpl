<style>
	.fieldList {
		display: table;
	}

	.fieldList > li {
		display: table-row;
	}

	.fieldList label, .fieldList input, .fieldList select {
		display: table-cell;
		width: 130px;
		margin-bottom: 1em;
	}

	.fieldList #eanValidity {
		display: inline-block;
		width: 16px;
		height: 16px;
	}

	#eanValidity.ui-state-default .ui-icon-circle-close {
		background-image: url("images/ui-icons_ff0000_256x240.png") !important;
	}

	#eanValidity.ui-state-default .ui-icon-check {
		background-image: url("images/ui-icons_008000_256x240.png") !important;
	}
</style>
<script>
	$(function() {
		'use strict';
		var eanConfig = null, eanGenerator = null;

		function preprocessEanInput(element, type) {
			element.checkFloatval();
			if (type !== 'float' || isNaN(element.val())) {
				return 'incorrect';
			}

			return {
				key : element.attr('id'),
				value : element.val()
			};
		};

		function postProcessEanInput(element, type, requestData, resultData) {
			if (requestData.key === 'baseEan') {
				eanConfig.BaseEan = resultData[requestData.key];
			} else if (requestData.key === 'maxNumberOfEan') {
				eanConfig.MaxNumberOfEan = resultData[requestData.key];
			} else {
				alert("error");
			}

			updateEanGenerator();
			return resultData[requestData.key];
		}

		function updateEanGenerator() {
			eanGenerator = new EanGenerator(eanConfig.BaseEan, eanConfig.MaxNumberOfEan);
		}


		$.when($.get('../api/config/ean', function(data, textStatus, jqXHR) {
			eanConfig = data.data;
		})).then(function() {

			updateEanGenerator();

			$('#baseEan').val(eanConfig.BaseEan).change(function() {
				$(this).apiUpdate('../api/config/ean', 'float', preprocessEanInput, postProcessEanInput);
			});
			$('#maxNumberOfEan').val(eanConfig.MaxNumberOfEan).change(function() {
				$(this).apiUpdate('../api/config/ean', 'float', preprocessEanInput, postProcessEanInput);
			});

			$('#itemId').change(function() {
				$('#generatedEan').val(eanGenerator.getEan($(this).val()));
			});

			$('#eanCheckField').change(function() {
				var icon = $('#eanValidity span').removeClass('ui-icon-circle-close').removeClass('ui-icon-check');
				console.log(icon);
				if (eanGenerator.valid($(this).val())) {
					icon.addClass('ui-icon-check');
				} else {
					icon.addClass('ui-icon-circle-close');
				}
			});

			$('#eanTabs').tabs().show();
		});

	});
</script>
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
