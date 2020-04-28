$(function() {
	$('button[data-action="importimage"]').click(function () {
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
});
