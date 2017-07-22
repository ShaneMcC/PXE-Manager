$(function() {
	$('a[data-action="duplicate"]').click(function () {
		var imageid = $(this).data('imageid');
		var imagename = $(this).data('imagename');

		$('#duplicateImageForm').attr('action', '{{ url('/images/') }}' + imageid + '/duplicate.json');
		$('#duplicateImageForm input[name="imageid"]').val();
		$('#duplicateImageForm input[name="newname"]').val(imagename + " (Duplicate)");

		var okButton = $('#duplicateImageModal button[data-action="ok"]');
		okButton.text("Create");

		okButton.off('click').click(function () {
			return postForm('#duplicateImageForm', '#duplicateImageModal div.errorLocation');
		});

		$('#duplicateImageModal').modal({'backdrop': 'static'});
		return false;
	});
});
