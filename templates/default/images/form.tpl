{% if image %}
<form method="post" id="imageform" action="{{ url("#{pathprepend}/images/#{image.id}/edit.json") }}">
{% else %}
<form method="post" id="imageform" action="{{ url("#{pathprepend}/images/create.json") }}">
{% endif %}
<input type="hidden" name="csrftoken" value="{{csrftoken}}">


<table id="imageinfo" class="table table-striped table-bordered">
	<tbody>
		<tr>
			<th>Name</th>
			<td class="mono" data-type="text" data-name="name" data-value="{{ image.name }}">{{ image.name }}</td>
		</tr>

		<tr>
			<th>Variables</th>
			<td>
				<table id="variables" class="table table-striped table-bordered">
					<tbody>
						<tr>
							<th>Name</th>
							<th>Description</th>
							<th>Type</th>
							<th class="editonly" style="display: none">Actions</th>
						</tr>
						{% set varid = 0 %}
						{% for var,vardata in image.variables %}
							<tr data-varid="{{ varid }}">
								<td data-name="name" data-value="{{ var }}">{{ var }}</td>
								<td data-name="description" data-value="{{ vardata.description }}">{{ vardata.description }}</td>
								<td data-name="type" data-type="select" data-options="variableTypes" data-value="{{ vardata.type }}">{{ vardata.type }}</td>
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

		<tr>
			<th>PXE Data</th>
			<td class="mono" data-rows="5" data-type="textfield" data-name="pxedata" data-value="{{ image.pxedata }}"><pre>{{ image.pxedata }}</pre></td>
		</tr>

		<tr>
			<th>Kickstart/Preseed Data</th>
			<td class="mono" data-rows="15" data-type="textfield" data-name="script" data-value="{{ image.script }}"><pre>{{ image.script }}</pre></td>
		</tr>

	</tbody>
</table>
</form>

<div class="row" id="formcontrols">
	<div class="col">
		<button type="button" data-action="editimage" class="btn btn-primary" role="button">Edit Image</button>
		<button type="button" data-action="saveimage" class="btn btn-success hidden" role="button">Save Changes</button>

		{% if image.id %}
			<div class="float-right">
				<button type="button" class="btn btn-danger" role="button" data-toggle="modal" data-target="#deleteModal" data-backdrop="static">Delete Image</button>
			</div>

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

<script src="{{ url('/assets/images/form.js') }}"></script>
