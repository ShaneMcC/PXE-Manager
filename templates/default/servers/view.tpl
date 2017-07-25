<H1>Server :: {{ server.name }}</H1>
<small><em><Strong>Last Modified:</Strong> {{ image.lastmodified | date }}</em></small><br><br>

{% embed 'servers/form.tpl' %}
	{% block left_buttons %}
		<a href="{{ url("#{pathprepend}/servers/#{server.id}/preview") }}" class="btn btn-info">Preview</a>
	{% endblock %}
{% endembed %}

<br><br>
<H2>Logs</H2>

{% if hasPermission(['edit_servers']) %}
<div class="float-right">
	<button type="button" class="btn btn-danger" role="button" data-toggle="modal" data-target="#clearModal" data-backdrop="static">Clear Logs</button>
	<br><br>
</div>
{% endif %}
<table id="serverlogs" class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="time">Time</th>
			<th class="type">Type</th>
			<th class="entry">Entry</th>
		</tr>
	</thead>
	<tbody>
		{% set found = false %}
		{% for log in serverlogs %}
		{% set found = true %}
			<tr>
				<td class="time">
					{{ log.time | date }}
				</td>
				<td class="type">
					{{ log.type }}
				</td>
				<td class="entry mono">
					{{ log.entry }}
				</td>
			</tr>
		{% endfor %}
		{% if not found %}
			<tr>
				<td class="nonefound" colspan="3">
					<em>There are no logs for this server</em>
				</td>
			</tr>
		{% endif %}
	</tbody>
</table>

{% if hasPermission(['edit_servers']) %}
	{% embed 'blocks/modal_confirm.tpl' with {'id': 'clearModal'} %}
		{% block title %}
			Clear Logs
		{% endblock %}

		{% block body %}
			Are you sure you want to clear the logs of this server?
			<br><br>
			This will remove all logs for this server and can not be undone.
		{% endblock %}

		{% block buttons %}
			<button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
			<form id="clearlogs" method="post" action="{{ url("#{pathprepend}/servers/#{server.id}/clearlogs") }}">
				<input type="hidden" name="csrftoken" value="{{csrftoken}}">
				<input type="hidden" name="confirm" value="true">
				<button type="submit" class="btn btn-danger">Clear Logs</button>
			</form>
		{% endblock %}
	{% endembed %}
{% endif %}
