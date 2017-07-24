$(function() {
	$('button[data-action="saveserver"]').click(function () {
		$('html,body').scrollTop(0);
		$('div#flashContainer .alert').remove()

		$.ajax({
			url: $('#serverform').attr('action'),
			type: $('#serverform').attr('method'),
			data: $('#serverform').serialize(),
			success: function(data) {
				if (data['success']) {
					window.location = data['location'];
				} else {
					showFlash('error', undefined, data['error'] !== undefined ? data['error'] : 'Unknown Error.');
				}
			},
			error: function(xhr, err) {
				showFlash('error', undefined, 'Unknown Error.');
			}
		});
		return false;
	});

	$('button[data-action="editserver"]').click(function () {
		if ($(this).data('action') == "editserver") {
			button = $(this);

			$.getJSON("{{ url('/images.json') }}", function( data ) {
				setImageData(data);

				$('#formcontrols button[data-action="saveserver"]').removeClass('hidden');
				setEditable($('table#serverinfo td[data-name]'));

				button.data('action', 'cancel');
				button.html('Cancel');
				button.removeClass('btn-primary');
				button.addClass('btn-warning');

				$('span.customButtons').hide();
			});
		} else if ($(this).data('action') == "cancel") {
			$('#formcontrols button[data-action="saveserver"]').addClass('hidden');
			cancelEdit($('table#serverinfo td[data-name]'));
			getCurrentVariables();

			$(this).data('action', 'editserver');
			$(this).html('Edit Server');
			$(this).addClass('btn-primary');
			$(this).removeClass('btn-warning');

			$('span.customButtons').show();
		}

		return false;
	});

	getCurrentVariables();
});

var options = { "images": { } };
var imageData = {}

function setImageData(data) {
	imageData = data;

	options["images"] = { };

	for (var k in data["images"]) {
		if (data["images"].hasOwnProperty(k)) {
			options["images"][k] = data["images"][k]["name"];
		}
	}

	console.log(options);
}

function getCurrentVariables(imageid) {
	serverid = $('#serverinfo').data('server-id');
	if (serverid == undefined) { serverid = -1; }

	var url = '{{ url("/servers") }}/' + serverid + '/variables';
	if (imageid !== undefined && imageid != '') {
		url = url + '/' + imageid
	}

	$.ajax({
		url: url,
		success: function(data) {
			$("#variablesContainer").html(data);

			if ($('button[data-action="editserver"]').data('action') == "cancel" || ($('button[data-action="editserver"]').length == 0 && $('button[data-action="saveserver"]').length != 0)) {
				setEditable($("td[data-name]", $("#variablesContainer")));
			}
		}
	});
}

function setEditable(element) {
	$(element).each(function (index) {
		var field = $(this);
		var value = (field.data('raw-value') !== undefined) ? field.data('raw-value') : field.data('value');
		var key = field.data('name');
		var fieldType = field.data('type');
		var fieldData = field.data('data');

		if (fieldType == 'textfield' || fieldType == 'text') {
			var rows = field.data('rows');
			if (rows === undefined) { rows = 5; }
			field.html('<textarea rows="' + rows + '" class="form-control mono" name="' + key + '">' + escapeHtml(value) + '</textarea>');
		} else if (fieldType == 'select' || fieldType == 'selectoption') {
			var selectOptions = [];
			var select = '';
			var selectedVal = undefined;
			var optionsData = field.data('options');

			if (options[optionsData] !== undefined) {
				selectOptions = options[optionsData];
			} else {
				selectOptions = {};
				$.each(fieldData.split("|"), function(index, chunk) {
					selectOptions[chunk] = chunk;
				});
			}

			select += '<select class="form-control form-control-sm" name="' + key + '">';
			$.each(selectOptions, function(optKey, desc) {
				if (value == optKey || selectedVal == undefined) { selectedVal = optKey; }
				select += '	<option ' + (value == optKey ? 'selected' : '') + ' value="' + optKey + '">' + desc + '</option>';
			});
			select += '</select>';

			field.html(select);
			if (key == 'image') {
				getCurrentVariables(selectedVal);
				$("select", field).change(function() {
					getCurrentVariables($("option:selected", this).val());
				});
			}
		} else if (fieldType == 'yesno') {
			var radioButtons = '';
			var badgeYes = field.data('badge-yes');
			var badgeNo = field.data('badge-no');

			radioButtons += '<div class="btn-group" data-toggle="buttons">';
			radioButtons += '  <label class="btn btn-sm" data-active="btn-' + badgeYes + '" data-inactive="btn-outline-' + badgeYes + '" data-toggle-class>';
			radioButtons += '    <input type="radio" name="' + key + '" value="true" autocomplete="off" ' + (value == "Yes" ? 'checked' : '') + '>Yes';
			radioButtons += '  </label>';
			radioButtons += '  <label class="btn btn-sm" data-active="btn-' + badgeNo + '" data-inactive="btn-outline-' + badgeNo + '" data-toggle-class>';
			radioButtons += '    <input type="radio" name="' + key + '" value="false" autocomplete="off" ' + (value == "No" ? 'checked' : '') + '>No';
			radioButtons += '  </label>';
			radioButtons += '</div>';
			radioButtons = $(radioButtons);
			field.html(radioButtons);


			// Change state.
			$('input[type=radio]', radioButtons).change(function() {
				var container = $(this).parent('label').parent('div');

				$('label[data-toggle-class]', container).each(function() {
					if ($(this).find('input[type=radio]:checked').length == 0) {
						$(this).removeClass($(this).attr('data-active'));
						$(this).addClass($(this).attr('data-inactive'));
					} else {
						$(this).addClass($(this).attr('data-active'));
						$(this).removeClass($(this).attr('data-inactive'));
					}
				});
			});

			// Set initial state
			$('label[data-toggle-class]', radioButtons).each(function() {
				if ($(this).find('input[type=radio]:checked').length == 0) {
					$(this).removeClass($(this).attr('data-active'));
					$(this).addClass($(this).attr('data-inactive'));
				} else {
					$(this).addClass($(this).attr('data-active'));
					$(this).removeClass($(this).attr('data-inactive'));
				}
			});
		} else if (fieldType == 'integer') {
			field.html('<input type="number" class="form-control form-control-sm" name="' + key + '" value="' + escapeHtml(value) + '">');
		} else {
			field.html('<input type="text" class="form-control form-control-sm" name="' + key + '" value="' + escapeHtml(value) + '">');
		}
	});
}

function cancelEdit(element) {
	$(element).each(function (index) {
		var field = $(this);
		var fieldType = field.data('type');
		var fieldName = field.data('name');
		var fieldValue = field.data('value')

		if (fieldType == 'textfield') {
			field.html('<pre>' + escapeHtml(fieldValue) + '</pre>');
		} else if (fieldType == 'yesno') {

			var badgeYes = field.data('badge-yes');
			var badgeNo = field.data('badge-no');

			if (field.data('value') == "Yes") {
				field.html('<span class="badge badge-' + badgeYes + '">' + escapeHtml(fieldValue) + '</span>');
			} else {
				field.html('<span class="badge badge-' + badgeNo + '">' + escapeHtml(fieldValue) + '</span>');
			}
		} else if (fieldName == 'image' && fieldValue == '') {
			field.html('<em>No Image</em>');
		} else {
			field.html(escapeHtml(fieldValue));
		}
	});
}
