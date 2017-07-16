<H1>Server :: {{ server.name }} :: Preview</H1>

<H2> Available Variables </H2>
The current variables and values for this server/image are:
<br>
<ul>
{% for k,v in validvars %}
	<li>
		<strong>{{ k }}</strong>: <code>{{ v }}</code>
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
