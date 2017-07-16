$(function() {
	$('button[data-action="saveimage"]').click(function () {
		$('html,body').scrollTop(0);
		$('div#flashContainer .alert').remove()

		$.ajax({
			url: $('#imageform').attr('action'),
			type: $('#imageform').attr('method'),
			data: $('#imageform').serialize(),
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

	$('button[data-action="editimage"]').click(function () {
		if ($(this).data('action') == "editimage") {
			$('#formcontrols button[data-action="saveimage"]').removeClass('hidden');
			setEditable($('table#imageinfo td[data-name]'));
			$('.editonly').show();

			$(this).data('action', 'cancel');
			$(this).html('Cancel');
			$(this).removeClass('btn-primary');
			$(this).addClass('btn-warning');
		} else if ($(this).data('action') == "cancel") {
			$('#formcontrols button[data-action="saveimage"]').addClass('hidden');
			cancelEdit($('table#imageinfo td[data-name]'));
			$('.editonly').hide();
			$('tr.newvar').remove();

			$(this).data('action', 'editimage');
			$(this).html('Edit Image');
			$(this).addClass('btn-primary');
			$(this).removeClass('btn-warning');
		}

		return false;
	});

	$('button[data-action="addVar"]').click(function () {
		addVar();
	});

	$('button[data-action="deleteVar"]').click(function () {
		var row = $(this).parent('td').parent('tr');

		if ($(this).data('action') == "deleteVar") {
			cancelEdit($('td[data-name]', row));
			var varid = row.data('varid');
			$(row).find('td.actions').append('<input type="hidden" data-marker="delete" name="var[' + varid + '][delete]" value="true">');
			row.addClass('deleted');
			$(this).data('action', 'undelete');
			$(this).html('Undelete');
		} else if ($(this).data('action') == "undelete") {
			setEditable($('td[data-name]', row));
			row.removeClass('deleted');
			$(row).find('input[data-marker="delete"]').remove();
			$(this).data('action', 'deleteVar');
			$(this).html('Delete');
		}
	});
});

var options = {};
var options = {"variableTypes": {"ipv4": "IPv4 Address",
                                 "string": "Text String",
                                 "text": "Multi-Line Text Data",
                                }
              };

function setEditable(element) {
	$(element).each(function (index) {
		var field = $(this);
		var value = (field.data('raw-value') !== undefined) ? field.data('raw-value') : field.data('value');
		var key = field.data('name');
		var fieldType = field.data('type');
		var varid = field.parent('tr').data('varid');

		if (varid !== undefined) {
			key = 'var[' + varid + '][' + key + ']';
		}

		if (fieldType == 'textfield') {
			var rows = field.data('rows');
			if (rows === undefined) { rows = 5; }
			field.html('<textarea rows="' + rows + '" class="form-control mono" name="' + key + '">' + escapeHtml(value) + '</textarea>');
		} else if (fieldType == 'select') {
			var selectOptions = field.data('options');
			var select = '';
			select += '<select class="form-control form-control-sm" name="' + key + '">';
			$.each(options[selectOptions], function(optKey, desc) {
				select += '	<option ' + (value == optKey ? 'selected' : '') + ' value="' + optKey + '">' + desc + '</option>';
			});
			select += '</select>';
			field.html(select);
		} else {
			field.html('<input type="text" class="form-control form-control-sm" name="' + key + '" value="' + escapeHtml(value) + '">');
		}
	});
}

function cancelEdit(element) {
	$(element).each(function (index) {
		var field = $(this);
		var fieldType = field.data('type');

		if (fieldType == 'textfield') {
			field.html('<pre>' + escapeHtml(field.data('value')) + '</pre>');
		} else {
			field.html(escapeHtml(field.data('value')));
		}
	});
}

var newVarCount = 0;

function addVar() {
	var table = $('table#variables');

	varid = newVarCount++;

	var row = '';
	row += '<tr data-varid="new_' + varid + '" class="newvar">';
	row += '	<td data-name="name" data-value=""></td>';
	row += '	<td data-name="description" data-value=""></td>';
	row += '	<td data-name="type" data-type="select" data-options="variableTypes" data-value="string"></td>';
	row += '	<td class="actions editonly">';
	row += '		<button type="button" class="btn btn-sm btn-danger" data-action="deleteVar" role="button">Delete</button>';
	row += '	</td>';
	row += '</tr>';

	row = $(row);
	table.append(row);

	row.find('button[data-action="deleteVar"]').click(function () {
		var row = $(this).parent('td').parent('tr');
		row.remove();
		return false;
	});

	setEditable($('td[data-name]', row));
}

