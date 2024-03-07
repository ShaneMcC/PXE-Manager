<table id="variables" class="table table-striped table-bordered">
	<tbody>
		<input type="hidden" name="var" value="">
		{% for var,vardata in image.requiredvariables | filter(v => v.type != 'none') -%}
			<tr>
				<th>{{ vardata.description }}</th>

				{% if vardata.type == 'yesno' %}
					<td data-name="var[{{ var }}]" data-type="{{ vardata.type }}" data-default="{{ vardata.default }}" data-value="{{ server.variables[var] | yesno(vardata.default) }}" data-badge-yes="success" data-badge-no="danger">
						{% if server.variables[var] == 'true' %}
							<span class="badge badge-success">Yes</span>
						{% else %}
							<span class="badge badge-danger">No</span>
						{% endif %}
					</td>
				{% else %}
					<td data-name="var[{{ var }}]" data-type="{{ vardata.type }}" data-default="{{ vardata.default }}" data-value="{{ server.variables[var] }}" data-data="{{ vardata.data }}">
						{% if vardata.type == 'text' and server.variables[var] == '' %}
							<small><em><pre>{{ vardata.default }}</pre></em></small>
						{% elseif vardata.type == 'text' %}
							<pre>{{ server.variables[var] }}</pre>
						{% elseif vardata.type == 'password' %}
							<small><em>Hidden</em></small>
						{% elseif server.variables[var] == '' %}
							<small><em>{{ vardata.default }}</em></small>
						{% else %}
							{{ server.variables[var] }}
						{% endif %}
					</td>
				{% endif %}
			</tr>
		{% endfor %}
	</tbody>
</table>
