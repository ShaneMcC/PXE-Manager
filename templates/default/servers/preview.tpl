<H1>Server :: {{ server.name }} :: Preview</H1>

{% if pxedata %}
	<H2> Available Variables </H2>
	The current variables and values for this server/image are:
	<br>
	<ul>
	{% for k,v in validvars %}
		<li>
			{% if hiddenvars[k] %}
				<strong>{{ k }}</strong>: <em><small>Hidden</small></em>
			{% else %}
				<strong>{{ k }}</strong>: <code>{{ v | vardisplay }}</code>
			{% endif %}
		</li>
	{% endfor %}
	</ul>

	<H2> PXE Data </H2>
	<pre class="preview">{{ pxedata }}</pre>
	<br><br>

	<H2> Kickstart/Preseed Data </H2>
	<pre class="preview">{{ kickstart }}</pre>
	<br><br>

	<H2> Post-Install Script </H2>
	<pre class="preview">{{ postinstall }}</pre>
	<br><br>
{% else %}
	Server has no image assigned.
{% endif %}
