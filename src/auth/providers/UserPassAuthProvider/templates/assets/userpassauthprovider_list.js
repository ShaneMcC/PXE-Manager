$(function() {
	$('button[data-permission]').click(function () {
		var user = $(this).data('user');
		var permission = $(this).data('permission');
		var col = $(this).closest('td');
		var row = col.closest('tr');
		var valueSpan = row.find('span.value[data-permission=' + permission + ']');

		var button = this;

		button.disabled = true;

		// Toggle permissions.
		var setPermissions = {'permissions': {}};
		setPermissions['permissions'][permission] = (valueSpan.text().trim() == 'Yes' ? 'False' : 'True');

		setPermissions['csrftoken'] = $('#csrftoken').val();

		$.ajax({
			url: "{{ url('/users/action') }}/setPermission/" + user,
			data: setPermissions,
			method: "POST",
		}).done(function(data) {
			if (data['error'] !== undefined) {
				alert('There was an error: ' + data['error']);
			} else if (data['response'] !== undefined) {
				var result = data['response']['permissions'][permission]
				var newVal = (result === true || result == 'true') ? "Yes" : "No";
				var classVal = valueSpan.data('class-' + newVal.toLowerCase().trim());
				var classOldVal = valueSpan.data('class-' + valueSpan.text().toLowerCase().trim());

				valueSpan.text(newVal);
				valueSpan.removeClass(classOldVal);
				valueSpan.addClass(classVal);

				row.fadeOut(100).fadeIn(100);
			}

			button.disabled = false;
		}).fail(function(data) {
			alert('There was an error: ' + data.responseText);
			button.disabled = false;
		});
	});

	$('button[data-action=editpermissions]').click(function () {
		var col = $(this).closest('td');

		col.find('div.permissionsText').hide();
		col.find('table.permissionsTable').show();
	});

	$('button[data-user-action]').click(function () {
		var action = $(this).data('user-action');
		var user = $(this).data('user');
		var col = $(this).closest('td');
		var row = col.closest('tr');
		var value = col.find('span.value');
		var extra = $(this).data('extra') ? $(this).data('extra') : '';

		var button = this;
		button.disabled = true;

		if ($(this).data('extra-prompt')) {
			var extra = prompt($(this).data('extra-prompt'));
		}

		$.ajax({
			url: "{{ url('/users/action') }}/" + action + "/" + user,
			data: {'csrftoken': $('#csrftoken').val(), 'extra': extra},
			method: "POST",
		}).done(function(data) {
			if (data['error'] !== undefined) {
				alert('There was an error: ' + data['error']);
			} else if (data['response'] !== undefined) {
				if (data['response']['success'] !== undefined) {
					alert(data['response']['success']);
				} else {
					if (value.data('raw')) {
						var newVal = data['response'][value.data('field')];
						value.text(newVal);
					} else {
						var newVal = data['response'][value.data('field')] == true ? "Yes" : "No";
						var classVal = value.data('class-' + newVal.toLowerCase().trim());
						var classOldVal = value.data('class-' + value.text().toLowerCase().trim());

						value.text(newVal);
						value.removeClass(classOldVal);
						value.addClass(classVal);
					}

					if (action == "suspend" || action == "unsuspend") {
						row.find('span[data-showsuspend]').each(function() {
							if ($(this).data('showsuspend') == newVal) {
								$(this).show();
							} else {
								$(this).hide();
							}
						});
					}

					row.fadeOut(100).fadeIn(100);
				};
			}

			button.disabled = false;
		}).fail(function(data) {
			alert('There was an error: ' + data.responseText);
			button.disabled = false;
		});
	});


	$('button[data-action="deleteuser"]').click(function () {
		var user = $(this).data('id');
		var row = $(this).closest('tr');

		var okButton = $('#confirmDelete button[data-action="ok"]');
		okButton.removeClass("btn-success").addClass("btn-danger").text("Delete User");

		okButton.off('click').click(function () {
			$.ajax({
				url: "{{ url('/users/delete') }}/" + user,
				data: {'csrftoken': $('#csrftoken').val()},
				method: "POST",
			}).done(function(data) {
				if (data['error'] !== undefined) {
					alert('There was an error: ' + data['error']);
				} else if (data['response'] !== undefined) {
					row.fadeOut(500, function(){ $(this).remove(); });
				}
			}).fail(function(data) {
				alert('There was an error: ' + data.responseText);
			});
		});

		$('#confirmDelete').modal({'backdrop': 'static'});
	});

	$("#adduser").validate({
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
			username: {
				required: true,
			},
			realname: {
				required: true
			}
		},
	});

	$("#edituser").validate({
		highlight: function(element) {
			$(element).closest('.form-group').addClass('has-danger');
		},
		unhighlight: function(element) {
			$(element).closest('.form-group').removeClass('has-danger');
		},
		errorClass: 'form-control-feedback',
		rules: {
			password: {
				required: false,
				minlength: 6,
			},
			confirmpassword: {
				required: false,
				equalTo: "#editpassword",
			},
			username: {
				required: true,
			},
			realname: {
				required: true
			}
		},
	});

	$('button[data-action="addNewUser"]').click(function () {
		var okButton = $('#createUser button[data-action="ok"]');
		okButton.text("Create");

		okButton.off('click').click(function () {
			if ($("#adduser").valid()) {
				$("#adduser").submit();
				$('#createUser').modal('hide');
			}
		});

		$("#adduser")[0].reset();

		var cancelButton = $('#createUser button[data-action="cancel"]');
		cancelButton.off('click').click(function () {
			$("#adduser").validate().resetForm();
		});

		$('#createUser').modal({'backdrop': 'static'});
	});



	$('button[data-action="edituser"]').click(function () {
		var okButton = $('#changeUser button[data-action="ok"]');
		okButton.text("Edit");

		okButton.off('click').click(function () {
			if ($("#edituser").valid()) {
				$("#edituser").submit();
				$('#changeUser').modal('hide');
			}
		});

		$("#edituser")[0].reset();

		var user = $(this).data('id');
		var row = $(this).closest('tr');

		$("#edituser").attr('action', "{{ url('/users/edit/') }}" + user);

		var username = $('td.username', row).data('username');
		var realname = $('td.realname', row).data('realname');

		$("#editusername").val(username);
		$("#editrealname").val(realname);

		var cancelButton = $('#changeUser button[data-action="cancel"]');
		cancelButton.off('click').click(function () {
			$("#edituser").validate().resetForm();
		});

		$('#changeUser').modal({'backdrop': 'static'});
	});
});
