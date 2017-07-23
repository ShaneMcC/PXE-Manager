<div class="container">
	<form class="form-signin small" method="post">
		<input type="hidden" name="csrftoken" value="{{csrftoken}}">
		<h1 class="form-signin-heading">Please Login</h1>

		{% for field,data in fields %}
			<label for="input{{ field }}" class="sr-only">{% if data.label %}{{ data.label }}{% else %}{{ field }}{% endif %}</label>
			<input type="{% if data.type %}{{ data.type }}{% else %}text{% endif %}" name="{{ field }}" id="input{{ field }}" class="form-control" placeholder="{% if data.placeholder %}{{ data.placeholder }}{% else %}{% endif %}" required>
		{% endfor %}

		<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

	</form>
</div>

<link href="{{ url('assets/loginauthprovider_style.css') }}" rel="stylesheet">
