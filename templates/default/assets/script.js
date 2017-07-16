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

	$('div#flashContainer').append($(html));
	$('div#flashContainer .alert').alert()
}
