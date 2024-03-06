{% if hasPermission(['edit_images']) %}
	{% if image %}
		<form method="post" id="imageform" action="{{ url("#{pathprepend}/images/#{image.id}/edit.json") }}">
	{% else %}
		<form method="post" id="imageform" action="{{ url("#{pathprepend}/images/create.json") }}">
	{% endif %}

	<input type="hidden" name="csrftoken" value="{{csrftoken}}">
{% endif %}

{% set variableTypes = [] %}
{% set variableTypes = variableTypes | merge({"ipv4": "IPv4 Address"}) %}
{% set variableTypes = variableTypes | merge({"ipv6": "IPv6 Address"}) %}
{% set variableTypes = variableTypes | merge({"ip": "IPv4 or IPv6 Address"}) %}
{% set variableTypes = variableTypes | merge({"string": "Text String"}) %}
{% set variableTypes = variableTypes | merge({"integer": "Integer"}) %}
{% set variableTypes = variableTypes | merge({"selectoption": "Select Option"}) %}
{% set variableTypes = variableTypes | merge({"text": "Multi-Line Text Data"}) %}
{% set variableTypes = variableTypes | merge({"yesno": "Boolean Value"}) %}
{% set variableTypes = variableTypes | merge({"none": "Hidden Option"}) %}

<table id="imageinfo" class="table table-striped table-bordered">
	<tbody>
		<tr class="name">
			<th>Name</th>
			<td data-type="string" data-name="name" data-value="{{ image.name }}">{{ image.name }}</td>
		</tr>

		<tr class="variables">
			<th>Variables</th>
			<td>
				<table id="variables" class="table table-striped table-bordered table-sortable">
					<tbody>
						<tr>
							<th class="handle editonly" style="display: none"></th>
							<th class="name">Name</th>
							<th class="description">Description</th>
							<th class="data">Data</th>
							<th class="type">Type</th>
							<th class="default">Default Value</th>
							<th class="required">Required</th>
							<th class="actions editonly" style="display: none">Actions</th>
						</tr>
						{% set varid = 0 %}
						{% for var,vardata in image.variables %}
							<tr class="sortable" data-varid="{{ varid }}">
								<td class="handle editonly" style="display: none"><span class="draganddrop"></span></td>
								<td class="name" data-name="name" data-value="{{ var }}">{{ var }}</td>
								<td class="description" data-name="description" data-value="{{ vardata.description }}">{{ vardata.description }}</td>
								<td class="data" data-name="data" data-value="{{ vardata.data }}">{{ vardata.data }}</td>
								<td class="type" data-name="type" data-type="select" data-options="variableTypes" data-raw-value="{{ vardata.type }}" {% if variableTypes[vardata.type] %}data-value="{{ variableTypes[vardata.type] }}"{% endif %}>
									{% if variableTypes[vardata.type] %}
										{{ variableTypes[vardata.type] }}
									{% else %}
										{{ vardata.type }}
									{% endif %}
								</td>
								<td class="default" data-name="default" data-value="{{ vardata.default }}">{{ vardata.default }}</td>
								<td class="required" data-type="yesno" data-name="required" data-badge-yes="success" data-badge-no="danger" data-value="{{ vardata.required | yesno }}">
									{% if vardata.required %}
										<span class="badge badge-success">Yes</span>
									{% else %}
										<span class="badge badge-danger">No</span>
									{% endif %}
								</td>
								<td class="actions editonly" style="display: none">
									<button type="button" class="btn btn-sm btn-danger" data-action="deleteVar" role="button">Delete</button>
								</td>
							</tr>
							{% set varid = varid + 1 %}
						{% endfor %}
					</tbody>
				</table>

				<div class="float-right editonly" style="display: none">
					<button type="button" class="btn btn-success" role="button" data-action="addVar">Add</button>
				</div>
			</td>
		</tr>

		<tr class="pxedata scriptdata">
			<th>PXE Data</th>
			<td class="mono" data-codemirror="true" data-rows="5" data-type="textfield" data-name="pxedata" data-value="{{ image.pxedata }}"><pre>{{ image.pxedata }}</pre></td>
		</tr>

		<tr class="script scriptdata">
			<th>Kickstart/Preseed Data</th>
			<td class="mono" data-codemirror="true" data-rows="15" data-type="textfield" data-name="script" data-value="{{ image.script }}"><pre>{{ image.script }}</pre></td>
		</tr>

		<tr class="postinstall scriptdata">
			<th>Post-Install Script</th>
			<td class="mono" data-codemirror="true" data-rows="15" data-type="textfield" data-name="postinstall" data-value="{{ image.postinstall }}"><pre>{{ image.postinstall }}</pre></td>
		</tr>

		<tr>
			<th>Available for use?</th>
			<td data-type="yesno" data-name="available" data-badge-yes="success" data-badge-no="danger" data-value="{{ (not image or image.available) | yesno }}">
				{% if (not image or image.available) %}
					<span class="badge badge-success">Yes</span>
				{% else %}
					<span class="badge badge-danger">No</span>
				{% endif %}
			</td>
		</tr>

	</tbody>
</table>
</form>

<div class="row" id="formcontrols">
	<div class="col">
		{% if hasPermission(['edit_images']) %}
			<button type="button" data-action="editimage" class="btn btn-primary" role="button">Edit Image</button>
			<button type="button" data-action="saveimage" class="btn btn-success hidden" role="button">Save Changes</button>
		{% endif %}
		<span class="customButtons">
		{% block left_buttons %}{% endblock %}
		</span>

		<div class="float-right">
			{% if image.id and hasPermission(['edit_images']) %}
				<button type="button" class="btn btn-danger" role="button" data-toggle="modal" data-target="#deleteModal" data-backdrop="static">Delete Image</button>
			{% endif %}
			<span class="customButtons">
			{% block right_buttons %}{% endblock %}
			</span>
		</div>
		{% if image.id and hasPermission(['edit_images']) %}
			{% embed 'blocks/modal_confirm.tpl' with {'id': 'deleteModal'} %}
				{% block title %}
					Delete Image
				{% endblock %}

				{% block body %}
					Are you sure you want to delete this image?
					<br><br>
					This will cause all servers currently booting this image to revert to the default boot image.
				{% endblock %}

				{% block buttons %}
					<button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
					<form id="deleteimage" method="post" action="{{ url("#{pathprepend}/images/#{image.id}/delete") }}">
						<input type="hidden" name="csrftoken" value="{{csrftoken}}">
						<input type="hidden" name="confirm" value="true">
						<button type="submit" class="btn btn-danger">Delete Image</button>
					</form>
				{% endblock %}
			{% endembed %}
		{% endif %}
	</div>
</div>

<br><br>
<div class="helptext">
<H2> Image Templating </H2>
The PXE Data, kickstart/preseed script and Post-Install script values are all template-driven powered by <a href="https://twig.sensiolabs.org">Twig</a>.

Variables defined in images can be accessed in scripts using <code>{% verbatim %}{{ getVariable('<kbd>varname</kbd>') }}{% endverbatim %}</code> which will use the value as defined by the specific server.

In addition to variables, there are other functions that can be used:
<ul>
	<li>
		<code>{% verbatim %}{{ getServiceURL() }}{% endverbatim %}</code> - Get the service-url. (Used for example to disable a server after deployment: <code>{% verbatim %}wget {{ getServiceURL() }}/disable{% endverbatim %}</code>)
	</li>
	<li>
		<code>{% verbatim %}{{ getLogUrl('type', 'entry') }}{% endverbatim %}</code> - Shorthand to get the URL to log an event. (Eg: <code>{% verbatim %}wget -qO /dev/null "{{ getLogUrl('info', 'Started "POST"') }}"{% endverbatim %}</code>)
	</li>
	<li>
		<code>{% verbatim %}{{ getScriptURL() }}{% endverbatim %}</code> - Shorthand to get the URL for the <code>/script</code> service (kickstart/preseed data).
	</li>
	<li>
		<code>{% verbatim %}{{ getPostInstallURL() }}{% endverbatim %}</code> - Shorthand to get the URL for the <code>/postinstall</code> service (post-install script).
	</li>
</ul>
</div>

<script src="{{ url('/assets/images/form.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-sortable/0.9.13/jquery-sortable-min.js"></script>
<link href="{{ url('/assets/images/form.css') }}" rel="stylesheet">

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/mode/overlay.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/twig/twig.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/neat.css" />

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
