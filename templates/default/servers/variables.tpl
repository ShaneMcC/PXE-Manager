<table id="variables" class="table table-striped table-bordered">
	<tbody>
		<input type="hidden" name="var" value="">
		{% for var,vardata in image.requiredvariables %}
			<tr>
				<th>{{ vardata.description }}</th>

				{% if vardata.type == 'yesno' %}
					<td data-name="var[{{ var }}]" data-type="{{ vardata.type }}" data-value="{{ server.variables[var] | yesno }}" data-badge-yes="success" data-badge-no="danger">
						{% if server.variables[var] == 'true' %}
							<span class="badge badge-success">Yes</span>
						{% else %}
							<span class="badge badge-danger">No</span>
						{% endif %}
					</td>
				{% else %}
					<td data-name="var[{{ var }}]" data-type="{{ vardata.type }}" data-value="{{ server.variables[var] }}" data-data="{{ vardata.data }}">
						{% if vardata.type == 'text' %}
							<pre>{{ server.variables[var] }}</pre>
						{% else %}
							{{ server.variables[var] }}
						{% endif %}
					</td>
				{% endif %}
			</tr>
		{% endfor %}
	</tbody>
</table>
