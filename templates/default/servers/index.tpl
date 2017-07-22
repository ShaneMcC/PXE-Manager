<H1>Servers</H1>

<input class="form-control" data-search-top="table#serverlist" value="" placeholder="Search..."><br>

{% if hasPermission(['edit_servers']) %}
	<div class="float-right">
		<a href="{{ url('/servers/create') }}" class="btn btn-success">Add Server</a>
	</div>
	<br><br>
{% endif %}

<table id="serverlist" class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="server">Server</th>
			<th class="image">Image</th>
			<th class="enabled">Enabled</th>
			<th class="actions">Actions</th>
		</tr>
	</thead>
	<tbody>
		{% for server in servers %}
		<tr data-searchable-value="{{ server.name }}||{{ server.imagename }}">
			<td class="server">
				{{ server.name }}
			</td>
			<td class="image">
				{{ server.imagename }}
			</td>
			<td class="enabled">
				{% if server.enabled == 'true' %}
					<span class="badge badge-success">
						Yes
					</span>
				{% else %}
					<span class="badge badge-danger">
						No
					</span>
				{% endif %}
			</td>
			<td class="actions">
				<a href="{{ url('/servers/' ~ server.id) }}" class="btn btn-success">View</a>
				<a href="{{ url('/servers/' ~ server.id ~ '/preview') }}" class="btn btn-primary">Preview</a>
				{% if hasPermission(['edit_servers']) %}
					<a href="#" data-action="duplicate" data-serverid="{{ server.id }}" data-servername="{{ server.name }}" class="btn btn-primary">Duplicate</a>
				{% endif %}
			</td>
		</tr>
		{% endfor %}
	</tbody>
</table>

{% if hasPermission(['edit_servers']) %}
	{% embed 'blocks/modal_confirm.tpl' with {'id': 'duplicateServerModal', 'csrftoken': csrftoken} only %}
		{% block title %}
			Duplicate Server
		{% endblock %}

		{% block body %}
			<div class="errorLocation"></div>
			<form id="duplicateServerForm" method="post" action="">
				<input type="hidden" name="csrftoken" value="{{ csrftoken }}">
				<div class="form-group row">
					<label for="newname" class="col-4 col-form-label">Name</label>
					<div class="col-8">
						<input class="form-control" type="text" value="" id="newname" name="newname">
					</div>
				</div>

				<div class="form-group row">
					<label for="newmac" class="col-4 col-form-label">MAC Address</label>
					<div class="col-8">
						<input class="form-control" type="text" value="" id="newmac" name="newmac">
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

<script src="{{ url('/assets/servers/index.js') }}"></script>
