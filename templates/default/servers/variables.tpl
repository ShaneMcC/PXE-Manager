<table id="variables" class="table table-striped table-bordered">
	<tbody>
		{% for var,vardata in image.variables %}
			<tr>
				<th>{{ vardata.description }}</th>
				<td data-name="var[{{ var }}]" data-type="{{ vardata.type }}" data-value="{{ server.variables[var] }}">
					{% if vardata.type == 'text' %}
						<pre>{{ server.variables[var] }}</pre>
					{% else %}
						{{ server.variables[var] }}
					{% endif %}
				</td>
			</tr>
		{% endfor %}
	</tbody>
</table>
