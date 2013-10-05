$.fn.checkIntval = function() {
	var val = parseInt(this.val().replace(',', '.'));
	if (isNaN(val)) {
		this.val('not a number');
	} else {
		this.val(val);
	}

	return this;
};

$.fn.checkFloatval = function() {
	var val = parseFloat(this.val().replace(',', '.'));
	if (isNaN(val)) {
		this.val('not a number');
	} else {
		this.val(val);
	}

	return this;
};

$.fn.updateConfig = function() {
	if (!isNaN($(this).val())) {
		var data = {
			key : $(this).attr('id'),
			value : $(this).val()
		};

		console.log("trying to send data", data);

		var element = $(this);

		// disable field during post
		element.prop('disabled',true);

		$.post('updateConfig.php5', data, function(result) {

			// re-enable field after post
			element.prop('disabled',false);
			if (result != "") {
				alert(result);
			}
		});
	}

	return this;
};

$(document).ready(function() {

	var integerInputfields = $('#calculationTimeSingleWeighted, #calcualtionTimeDoubleWeighted, #minimumToleratedSpikes');
	var floatInputFields = $('#standardDeviationFactor, #spikeTolerance');

	integerInputfields.change(function() {
		$(this).checkIntval();
		$(this).updateConfig();
	});

	floatInputFields.change(function() {
		$(this).checkFloatval();
		$(this).updateConfig();
	});

	integerInputfields.add(floatInputFields).mouseup(function(e) {
		e.preventDefault();
	}).focus(function() {
		if (isNaN($(this).val()))
			$(this).val("");
		else
			$(this).select();
	});

	$('#resultTable').flexigrid({
		url : 'post-xml.php5',
		dataType : 'xml',
		colModel : [{
			display : 'Item ID',
			name : 'ItemID',
			width : 40,
			sortable : true,
			align : 'center'
		}, {
			display : 'Name',
			name : 'Name',
			width : 180,
			sortable : true,
			align : 'left'
		}, {
			display : 'durchschnittlicher Bedarf (Monat)',
			name : 'MonthlyNeed',
			width : 120,
			sortable : true,
			align : 'right'
		}, {
			display : 'durchschnittlicher Bedarf (Tag)',
			name : 'DailyNeed',
			width : 130,
			sortable : true,
			align : 'right'
		}, {
			display : 'Markierung',
			name : 'Marking',
			width : 80,
			sortable : true,
			align : 'center'
		}, {
			display : 'Empfehlung Meldebestand (Meldebestand alt)',
			name : 'ItemID',
			width : 80,
			sortable : false,
			align : 'right'
		}, {
			display : 'Mindesabnahme / Bestellvorschlag (Bestellvorschlag aktuell)',
			name : 'min_purchase_order_suggestion',
			width : 80,
			sortable : false,
			align : 'right',
			hide : true
		}, {
			display : 'Änderung',
			name : 'change',
			width : 80,
			sortable : false,
			align : 'right',
			hide : true
		}, {
			display : 'Status Meldebestand',
			name : 'status_reorder_level',
			width : 80,
			sortable : false,
			align : 'right',
			hide : true
		}, {
			display : 'Datum',
			name : 'Date',
			width : 120,
			sortable : true,
			align : 'right'
		}],
		buttons : [{
			name : 'Kalkulation manuell auslösen',
			bclass : 'gear',
			onpress : function() {
				var currentGrid = $('#resultTable');
				$('body').addClass('loading');
				$.get('executeCalculation.php5', function() {
					$('body').removeClass("loading");
					currentGrid.flexReload();
				});
			}
		}],
		onSuccess : function() {
			// adjust table height
			$('#resultTable').parent().css('height', $('#resultTable').outerHeight());
			
			// adjust classes for markingid column:
			$('#resultTable td[abbr="Marking"]').each(function(){
				
				var markingDiv = $(this).find('div');
				
				// get id ...
				var id = parseInt($(markingDiv).html());
				
				if ($.inArray(id,[4,9,16,20]) > -1){
					// set class ...
					$(markingDiv).addClass('markingIDCell_' + id);

					// ... and clear cell afterwards
					$(markingDiv).html('&nbsp;');
				} else if (id == 0) {
					$(markingDiv).html('keine');
				} else {
					$(markingDiv).html('FEHLER!');
				}
			});
		},
		searchitems : [{
			display : 'ItemID',
			name : 'ItemID'
		}, {
			display : 'Name',
			name : 'Name',
			isdefault : true
		}],
		sortname : "ItemID",
		sortorder : "asc",
		usepager : true,
		singleSelect : true,
		title : 'Artikel',
		useRp : true,
		rp : 15,
		showTableToggleBtn : false,
		pagetext : 'Seite',
		outof : 'von',
		pagestat : 'Zeige {from} bis {to} von {total} Artikeln',
		procmsg : 'Bitte warten...'
	});
	
	appendFilterSelection();
});

function appendFilterSelection(){
	var filterSelection = new Array(4,9,16,20);
	
	var chkBxDiv = document.createElement('div');
	chkBxDiv.className = 'fButton';
	chkBxDiv.id = 'filterSelection';
	$(chkBxDiv).append('<span>Filter:</span>');
	
	var tForm = document.createElement('form');
	$(chkBxDiv).append(tForm);
	
	for (i=0; i< filterSelection.length;i++){
		var id = filterSelection[i];
		var div = document.createElement('div');
		div.id = 'markingID_' + id;
		var box = document.createElement('input');
		var label = document.createElement('label');
				
		box.type = 'checkbox';
		box.id = 'markingID_' + id + '_field';
		label.htmlFor = box.id;
			
		$(div).append(box);
		$(div).append(label);
		$(tForm).append(div);
	}
	
	$('.tDiv2').append(chkBxDiv);
}
