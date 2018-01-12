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

	$('.table-sortable tbody').sortable({
		containerSelector: 'table',
		itemPath: '> tbody',
		itemSelector: 'tr.sortable',
		placeholder: '<tr class="placeholders"><td colspan="7"></td></tr>',
		handle: 'td.handle',
	});
	$('.table-sortable tbody').sortable("disable");

	$('button[data-action="editimage"]').click(function () {
		if ($(this).data('action') == "editimage") {
			$('#formcontrols button[data-action="saveimage"]').removeClass('hidden');
			setEditable($('table#imageinfo td[data-name]'));
			$('.editonly').show();

			$('.table-sortable tbody').sortable("enable");

			$(this).data('action', 'cancel');
			$(this).html('Cancel');
			$(this).removeClass('btn-primary');
			$(this).addClass('btn-warning');
		} else if ($(this).data('action') == "cancel") {
			$('#formcontrols button[data-action="saveimage"]').addClass('hidden');
			cancelEdit($('table#imageinfo td[data-name]'));
			$('.editonly').hide();
			$('tr.newvar').remove();

			$('.table-sortable tbody').sortable("disable");

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

	CodeMirror.defineMode("shelltwig", function(config, parserConfig) {
	  return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "shell"), CodeMirror.getMode(config, "twig"));
	});
});

var options = {};
var options = {"variableTypes": {"ipv4": "IPv4 Address",
                                 "ipv6": "IPv6 Address",
                                 "ip": "IPv4 or IPv6 Address",
                                 "string": "Text String",
                                 "integer": "Integer",
                                 "text": "Multi-Line Text Data",
                                 "selectoption": "Select Option",
                                 "none": "Hidden Option",
                                 "yesno": "Boolean Value",
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

			if (field.data('codemirror')) {
				var cmmode = field.data('codemode') !== undefined ? field.data('codemode') : "shelltwig";

				var textarea = $('textarea', field)[0];
				var editor = CodeMirror.fromTextArea(textarea, {
					lineNumbers: true,
					styleActiveLine: true,
					lineWrapping: true,
					mode: cmmode,
					theme: "neat",
				});

				editor.on('change', editor => {
					textarea.value = editor.getValue();
				});

				$(editor.getWrapperElement()).resizable({
					resize: function() {
						editor.setSize($(this).width(), $(this).height());
						editor.refresh();
					}
				});
			}
		} else if (fieldType == 'select') {
			var selectOptions = field.data('options');
			var select = '';
			select += '<select class="form-control form-control-sm" name="' + key + '">';
			$.each(options[selectOptions], function(optKey, desc) {
				select += '	<option ' + (value == optKey ? 'selected' : '') + ' value="' + optKey + '">' + desc + '</option>';
			});
			select += '</select>';
			field.html(select);
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
	row += '<tr data-varid="new_' + varid + '" class="newvar sortable">';
	row += '	<td class="handle"><span class="draganddrop"></span></td>';
	row += '	<td data-name="name" data-value=""></td>';
	row += '	<td data-name="description" data-value=""></td>';
	row += '	<td data-name="data" data-value=""></td>';
	row += '	<td data-name="type" data-type="select" data-options="variableTypes" data-value="string"></td>';
	row += '	<td class="required" data-type="yesno" data-name="required" data-badge-yes="success" data-badge-no="danger" data-value="Yes">';
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

