<H1>User List</H1>

<input type="hidden" id="csrftoken" value="{{csrftoken}}">
<input class="form-control" data-search-top="table#userlist" value="" placeholder="Search..."><br>

{% if hasPermission(['edit_users']) %}
<div class="float-right">
	<button type="button" data-action="addNewUser" class="btn btn-success">Add User</button>
</div>
<br><br>
{% endif %}

<table id="userlist" class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="id">ID</th>
			<th class="username">Username</th>
			<th class="realname">Realname</th>
			<th class="permissions">Permissions</th>
			<th class="state">Enabled</th>
			{% if hasPermission(['edit_users']) %}
				<th class="actions">Actions</th>
			{% endif %}
		</tr>
	</thead>
	<tbody>
		{% for userinfo in users %}
		<tr data-searchable-value="{{ userinfo.username }}||{{ userinfo.realname }}">
			<td class="id">
				{{ userinfo.id }}
			</td>
			<td class="username" data-username="{{ userinfo.username }}">
				{{ userinfo.username }}
			</td>
			<td class="realname" data-realname="{{ userinfo.realname }}">
				{{ userinfo.realname }}
			</td>
			<td class="permissions">
				<div class="permissionsText">
					<span> {{ userinfo.permissions | keys | join(', ') }} </span>
					{% if hasPermission(['edit_users']) %}
						{% if userinfo.permissions|keys|length > 0 %}<br>{% endif %}
						<button data-action="editpermissions" class="btn btn-sm btn-info">Edit Permissions</button>
					{% endif %}
				</div>
				<table class="permissionsTable table table-sm hidden">
				{% for permission in validPermissions %}
					<tr>
						<td class="name">
							{{ permission }}
						</td>
						<td class="value">
							<span class="value badge {% if userinfo.permissions[permission] == 'true' %}badge-primary{% else %}badge-default{% endif %}" data-permission="{{ permission }}" data-class-yes="badge-primary" data-class-no="badge-default">
								{{ userinfo.permissions[permission] | yesno }}
							</span>
						</td>
						<td class="actions">
							{% if ((userinfo.id != myuser.id or (permission != "edit_users" and permission != "view_users")) and hasPermission(['edit_users'])) %}
								<button type="button" data-permission="{{ permission }}" data-user="{{ userinfo.id }}" class="btn btn-sm btn-info">Toggle</button>
							{% endif %}
						</td>
					</tr>
				{% endfor %}
				</table>
			</td>
			<td class="state">
				<span class="value badge {% if userinfo.enabled %}badge-success{% else %}badge-danger{% endif %}" data-field="enabled" data-class-yes="badge-success" data-class-no="badge-danger">
					{{ userinfo.enabled | yesno }}
				</span>
				{% if userinfo.id != myuser.id and hasPermission(['edit_users']) %}
					<span class="action {% if userinfo.enabled %}hidden{% endif %}" data-showsuspend="No">
						<button type="button" data-user-action="unsuspend" data-user="{{ userinfo.id }}" class="btn btn-sm btn-info float-right">Enable</button>
					</span>
					<span class="action {% if not userinfo.enabled %}hidden{% endif %}" data-showsuspend="Yes">
						<button type="button" data-user-action="suspend" data-user="{{ userinfo.id }}" class="btn btn-sm btn-warning float-right">Disable</button>
					</span>
				{% endif %}
			</td>
			{% if hasPermission(['edit_users']) %}
				<td class="actions">
					{% if userinfo.id != myuser.id %}
						<button data-action="deleteuser" data-id="{{ userinfo.id }}" class="btn btn-sm btn-danger">Delete</button>
					{% endif %}
					<button data-action="edituser" data-id="{{ userinfo.id }}" class="btn btn-sm btn-success">Edit</button>
				</td>
			{% endif %}
		</tr>
		{% endfor %}
	</tbody>
</table>

{% embed 'blocks/modal_confirm.tpl' with {'id': 'confirmDelete'} only %}
	{% block title %}
		Delete User
	{% endblock %}

	{% block body %}
		Are you sure you want to delete this user?
	{% endblock %}
{% endembed %}


{% if hasPermission(['edit_users']) %}
	{% embed 'blocks/modal_confirm.tpl' with {'id': 'createUser', 'large': true, 'csrftoken': csrftoken} only %}
		{% block title %}
			Add User
		{% endblock %}

		{% block body %}
			<form id="adduser" method="post" action="{{ url('/users/create') }}">
				<input type="hidden" name="csrftoken" value="{{csrftoken}}">
				<div class="form-group row">
					<label for="username" class="col-3 col-form-label">Username</label>
					<div class="col-9">
						<input class="form-control" type="username" value="" id="username" name="username">
					</div>
				</div>
				<div class="form-group row">
					<label for="realname" class="col-3 col-form-label">Real Name</label>
					<div class="col-9">
						<input class="form-control" type="text" value="" id="realname" name="realname">
					</div>
				</div>

				<div class="form-group row">
					<label for="password" class="col-3 col-form-label">Password</label>
					<div class="col-9">
						<input class="form-control" type="password" value="" id="password" name="password">
					</div>
				</div>

				<div class="form-group row">
					<label for="confirmpassword" class="col-3 col-form-label">Confirm Password</label>
					<div class="col-9">
						<input class="form-control" type="password" value="" id="confirmpassword" name="confirmpassword">
					</div>
				</div>
			</form>
		{% endblock %}

		{% block buttons %}
			<button type="button" data-action="cancel" class="btn btn-primary" data-dismiss="modal">Cancel</button>
			<button type="button" data-action="ok" class="btn btn-success">Ok</button>
		{% endblock %}
	{% endembed %}

	{% embed 'blocks/modal_confirm.tpl' with {'id': 'changeUser', 'large': true, 'csrftoken': csrftoken} only %}
		{% block title %}
			Edit User
		{% endblock %}

		{% block body %}
			<form id="edituser" method="post" action="">
				<input type="hidden" name="csrftoken" value="{{csrftoken}}">

				<div class="form-group row">
					<label for="username" class="col-3 col-form-label">Username</label>
					<div class="col-9">
						<input class="form-control" type="username" value="" id="editusername" name="username">
					</div>
				</div>
				<div class="form-group row">
					<label for="realname" class="col-3 col-form-label">Real Name</label>
					<div class="col-9">
						<input class="form-control" type="text" value="" id="editrealname" name="realname">
					</div>
				</div>

				<div class="form-group row">
					<label for="password" class="col-3 col-form-label">Password</label>
					<div class="col-9">
						<input class="form-control" type="password" value="" id="editpassword" name="password">
					</div>
				</div>

				<div class="form-group row">
					<label for="confirmpassword" class="col-3 col-form-label">Confirm Password</label>
					<div class="col-9">
						<input class="form-control" type="password" value="" id="editconfirmpassword" name="confirmpassword">
					</div>
				</div>
			</form>
		{% endblock %}

		{% block buttons %}
			<button type="button" data-action="cancel" class="btn btn-primary" data-dismiss="modal">Cancel</button>
			<button type="button" data-action="ok" class="btn btn-success">Ok</button>
		{% endblock %}
	{% endembed %}
{% endif %}

<script src="{{ url('assets/userpassauthprovider_list.js') }}"></script>
<link href="{{ url('assets/userpassauthprovider_style.css') }}" rel="stylesheet">
