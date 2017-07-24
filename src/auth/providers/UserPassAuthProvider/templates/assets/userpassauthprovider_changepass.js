$(function() {
	$("#changepasss").validate({
		highlight: function(element) {
			$(element).closest('.form-group').addClass('has-danger');
		},
		unhighlight: function(element) {
			$(element).closest('.form-group').removeClass('has-danger');
		},
		errorClass: 'form-control-feedback',
		rules: {
			password: {
				required: true,
				minlength: 6,
			},
			confirmpassword: {
				required: true,
				equalTo: "#password",
			},
		},
	});

	var okButton = $('button[data-action="ok"]');
	okButton.off('click').click(function () {
//		if ($("#changepass").valid()) {
			$("#changepass").submit();
//		}
	});

	var cancelButton = $('button[data-action="cancel"]');
	cancelButton.off('click').click(function () {
		window.location = "{{ url('/') }}"
	});
});
