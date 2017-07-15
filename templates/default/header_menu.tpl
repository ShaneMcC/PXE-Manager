<ul class="navbar-nav mr-auto">
{% for item in menu %}
	<li class="nav-item{% if item.active %} active{% endif %}">
		{% if item.link %}<a class="nav-link" href="{{ item.link }}">{% endif %}
		{{ item.title }}
		{% if item.active %}<span class="sr-only">(current)</span>{% endif %}
		{% if item.link %}</a>{% endif %}
	</li>
{% endfor %}
</ul>
