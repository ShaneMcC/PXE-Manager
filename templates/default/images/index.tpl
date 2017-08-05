<H1>Bootable Images</H1>

<input class="form-control" data-search-top="table#imagelist" value="" placeholder="Search..."><br>

{% if hasPermission(['edit_images']) %}
	<div class="float-right">
		<a href="{{ url('/images/create') }}" class="btn btn-success">Add Bootable Image</a>
	</div>
	<br><br>
{% endif %}

<table id="imagelist" class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="imageid">ID</th>
			<th class="image">Image</th>
			<th class="actions">Actions</th>
		</tr>
	</thead>
	<tbody>
		{% set found = false %}
		{% for image in images %}
		{% set found = true %}
		<tr data-searchable-value="{{ image.name }}">
			<td class="imageid">
				{{ image.id }}
			</td>
			<td class="image">
				{{ image.name }}
			</td>
			<td class="actions">
				<a href="{{ url('/images/' ~ image.id) }}" class="btn btn-success">View</a>
				{% if hasPermission(['edit_images']) %}
					<a href="{{ url('/images/' ~ image.id ~ '/duplicate') }}" data-action="duplicate" data-imageid="{{ image.id }}" data-imagename="{{ image.name }}" class="btn btn-primary">Duplicate</a>
				{% endif %}
			</td>
		</tr>
		{% endfor %}

		{% if not found %}
			<tr>
				<td class="nonefound" colspan="2">
					<em>There are no images</em>
				</td>
			</tr>
		{% endif %}
	</tbody>
</table>

{% if hasPermission(['edit_images']) %}
	{% embed 'blocks/modal_confirm.tpl' with {'id': 'duplicateImageModal', 'csrftoken': csrftoken} only %}
		{% block title %}
			Duplicate Image
		{% endblock %}

		{% block body %}
			<div class="errorLocation"></div>
			<form id="duplicateImageForm" method="post" action="">
				<input type="hidden" name="csrftoken" value="{{ csrftoken }}">
				<div class="form-group row">
					<label for="newname" class="col-4 col-form-label">New Name</label>
					<div class="col-8">
						<input class="form-control" type="text" value="" id="newname" name="newname">
					</div>
				</div>
			</form>
		{% endblock %}

		{% block buttons %}
			<button type="button" data-action="cancel" class="btn btn-primary" data-dismiss="modal">Cancel</button>
			<button type="button" data-action="ok" class="btn btn-success">Ok</button>
		{% endblock %}
	{% endembed %}
{% endif %}

<script src="{{ url('/assets/images/index.js') }}"></script>
