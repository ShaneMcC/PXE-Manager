<table id="variables" class="table table-striped table-bordered">
	<tbody>
		{% for var,vardata in image.variables %}
			<tr>
				<th>{{ vardata.description }}</th>
				<td data-name="var[{{ var }}]" data-type="{{ vardata.type }}" data-value="{{ server.variables[var] }}">{{ server.variables[var] }}</td>
			</tr>
		{% endfor %}
	</tbody>
</table>
