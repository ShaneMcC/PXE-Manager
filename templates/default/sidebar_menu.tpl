{% if title or showsearch %}
	<div class="nav-link">
		{% if title %}
			<strong>
				{{ title }}
			</strong>
		{% endif %}
		{% if showsearch %}
			<input class="form-control" data-search-top="nav#sidebar" value="" placeholder="Search...">
		{% endif %}
	</div>
{% endif %}

{% for section in menu %}
	<ul class="nav nav-pills flex-column">
	{% for item in section %}
		<li class="nav-item" {% if item.dataValue %}data-searchable-value="{{ item.dataValue }}"{% endif %}>
			{% if item.link %}
				{% if item.button %}
					<div class="nav-link">
					<a class="btn btn-block btn-{{ item.button }}"
				{% else %}
					<a class="nav-link{% if item.active %} active{% endif %}"
				{% endif %}
					href="{{ item.link }}"
					{% if item.action %} data-action="{{ item.action }}"{% endif %}
					{% if item.hover %} title="{{ item.hover }}"{% endif %}
				>

			{% elseif item.button %}
				<div class="nav-link">
				<button class="btn btn-block btn-{{ item.button }}" data-action="{{ item.action }}">
			{% else %}
				<div class="nav-link"><strong>
			{% endif %}
			{{ item.title }}
			{% if item.subtitle %}<small class="subtitle">({{item.subtitle}})</small>{% endif %}
			{% if item.active %}<span class="sr-only">(current)</span>{% endif %}
			{% if item.link %}
				</a>
				{% if item.button %}
				</div>
				{% endif %}
			{% elseif item.button %}
				</button></div>
			{% else %}
				</strong></div>
			{% endif %}
		</li>
	{% endfor %}
	</ul>
{% endfor %}
