<H1>Server :: {{ server.name }}</H1>

{% embed 'servers/form.tpl' %}
	{% block left_buttons %}
		<a href="{{ url("#{pathprepend}/servers/#{server.id}/preview") }}" class="btn btn-info">Preview</a>
	{% endblock %}
{% endembed %}
