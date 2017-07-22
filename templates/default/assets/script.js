$(function() {
	$(".alert").alert()

	$("input[data-search-top]").on('input', function() {
		var value = $(this).val();
		var searchTop = $(this).data('search-top');

		if (value == "") {
			$(searchTop).find("[data-searchable-value]").show();
		} else {
			var match = new RegExp('^.*' + escapeRegExp(value) + '.*$', 'i');

			$(searchTop).find("[data-searchable-value]").each(function() {
				var show = false;

				for (val in $(this).data('searchable-value').split("||")) {
					if ($(this).data('searchable-value').match(match)) {
						show = true;
						break;
					}
				}

				if (show) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		}
	});
});

function postForm(formid, errorLocation) {
	if (errorLocation == undefined) {
		errorLocation = 'div#flashContainer';
	}

	$('html,body').scrollTop(0);
	$(errorLocation + ' .alert').remove()

	$.ajax({
		url: $(formid).attr('action'),
		type: $(formid).attr('method'),
		data: $(formid).serialize(),
		success: function(data) {
			if (data['success']) {
				window.location = data['location'] !== undefined ? data['location'] : window.location;
			} else {
				showFlashLocation(errorLocation, 'error', undefined, data['error'] !== undefined ? data['error'] : 'Unknown Error.');
			}
		},
		error: function(xhr, err) {
			showFlashLocation(errorLocation, 'error', undefined, 'Unknown Error.');
		}
	});
	return false;
}

function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

var entityMap = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
  '/': '&#x2F;',
  '`': '&#x60;',
  '=': '&#x3D;'
};

function escapeHtml(string) {
  return String(string).replace(/[&<>"'`=\/]/g, function (s) {
    return entityMap[s];
  });
}

function showFlash(type, title, message) {
	showFlashLocation('div#flashContainer', type, title, message)
}

function showFlashLocation(location, type, title, message) {
	if (type == 'error') { type = 'danger'; }

	var html = '';
	html = html + '	<div class="alert alert-'+type+' alert-dismissible fade show" role="alert">';
	html = html + '		<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
	html = html + '			<span aria-hidden="true">&times;</span>';
	html = html + '		</button>';
	if (title != undefined) {
		html = html + '		<h4 class="alert-heading">' + title + '</h4>';
	}
	html = html + '			'+message;
	html = html + '	</div>';

	$(location).append($(html));
	$(location + ' .alert').alert()
}
