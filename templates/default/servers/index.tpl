<H1>Servers</H1>

<input class="form-control" data-search-top="table#serverlist" value="" placeholder="Search..."><br>

<div class="float-right">
	<a href="{{ url('/servers/create') }}" class="btn btn-success">Add Server</a>
</div>
<br><br>

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
			</td>
		</tr>
		{% endfor %}
	</tbody>
</table>
