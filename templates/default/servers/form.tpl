{% if hasPermission(['edit_servers']) %}
	{% if server %}
		<form method="post" id="serverform" action="{{ url("#{pathprepend}/servers/#{server.id}/edit.json") }}">
	{% else %}
		<form method="post" id="serverform" action="{{ url("#{pathprepend}/servers/create.json") }}">
	{% endif %}
	<input type="hidden" name="csrftoken" value="{{csrftoken}}">
{% endif %}


<table id="serverinfo" class="table table-striped table-bordered" {% if server %}data-server-id="{{ server.id }}"{% endif %}>
	<tbody>
		<tr>
			<th>Name</th>
			<td data-type="string" data-name="name" data-value="{{ server.name }}">
				{{ server.name }}
			</td>
		</tr>

		<tr>
			<th>MAC Address</th>
			<td data-type="string" data-name="macaddr" data-value="{{ server.macaddr }}">
				{{ server.macaddr }}
			</td>
		</tr>

		<tr>
			<th>Boot Image</th>
			<td data-type="select" data-options="images" data-name="image" data-raw-value="{{ server.image }}" data-value="{{ image.name }}">
				{% if image %}
					<a href="{{ url('/images/' ~ server.image) }}">{{ image.name }}</a>
				{% else %}
					<em>No Image</em>
				{% endif %}
			</td>
		</tr>

		<tr>
			<th>Variables</th>
			<td id="variablesContainer">

			</td>
		</tr>

		<tr>
			<th>Enabled</th>
			<td data-type="yesno" data-name="enabled" data-badge-yes="success" data-badge-no="danger" data-value="{{ (image and server.enabled  == 'true') | yesno }}">
				{% if image and server.enabled == 'true' %}
					<span class="badge badge-success">Yes</span>
				{% else %}
					<span class="badge badge-danger">No</span>
				{% endif %}
			</td>
		</tr>
	</tbody>
</table>
</form>

<div class="row" id="formcontrols">
	<div class="col">
		{% if hasPermission(['edit_servers']) %}
			<button type="button" data-action="editserver" class="btn btn-primary" role="button">Edit Server</button>
			<button type="button" data-action="saveserver" class="btn btn-success hidden" role="button">Save Changes</button>
		{% endif %}
		<span class="customButtons">
		{% block left_buttons %}{% endblock %}
		</span>

		<div class="float-right">
			{% if server.id and hasPermission(['edit_servers']) %}
				<button type="button" class="btn btn-danger" role="button" data-toggle="modal" data-target="#deleteModal" data-backdrop="static">Delete Server</button>
			{% endif %}
			<span class="customButtons">
			{% block right_buttons %}{% endblock %}
			</span>
		</div>
		{% if server.id and hasPermission(['edit_servers']) %}
			{% embed 'blocks/modal_confirm.tpl' with {'id': 'deleteModal'} %}
				{% block title %}
					Delete Server
				{% endblock %}

				{% block body %}
					Are you sure you want to delete this server?
					<br><br>
					This will remove all data for this server and can not be undone.
				{% endblock %}

				{% block buttons %}
					<button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
					<form id="deleteserver" method="post" action="{{ url("#{pathprepend}/servers/#{server.id}/delete") }}">
						<input type="hidden" name="csrftoken" value="{{csrftoken}}">
						<input type="hidden" name="confirm" value="true">
						<button type="submit" class="btn btn-danger">Delete Server</button>
					</form>
				{% endblock %}
			{% endembed %}
		{% endif %}
	</div>
</div>

<script src="{{ url('/assets/servers/form.js') }}"></script>
