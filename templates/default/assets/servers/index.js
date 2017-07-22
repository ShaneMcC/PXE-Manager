$(function() {
	$('a[data-action="duplicate"]').click(function () {
		var serverid = $(this).data('serverid');
		var servername = $(this).data('servername');

		$('#duplicateServerForm').attr('action', '{{ url('/servers/') }}' + serverid + '/duplicate.json');
		$('#duplicateServerForm input[name="newname"]').val(servername + " (Duplicate)");
		$('#duplicateServerForm input[name="newmac"]').val('');

		var okButton = $('#duplicateServerModal button[data-action="ok"]');
		okButton.text("Create");

		okButton.off('click').click(function () {
			return postForm('#duplicateServerForm', '#duplicateServerModal div.errorLocation');
		});

		$('#duplicateServerModal').modal({'backdrop': 'static'});
		return false;
	});
});
