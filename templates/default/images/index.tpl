<H1>Bootable Images</H1>

<input class="form-control" data-search-top="table#imagelist" value="" placeholder="Search..."><br>

<div class="float-right">
	<a href="{{ url('/images/create') }}" class="btn btn-success">Add Bootable Image</a>
</div>
<br><br>

<table id="imagelist" class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="image">Image</th>
			<th class="actions">Actions</th>
		</tr>
	</thead>
	<tbody>
		{% for image in images %}
		<tr>
			<td class="image">
				{{ image.name }}
			</td>
			<td class="actions">
				<a href="{{ url('/images/' ~ image.id) }}" class="btn btn-success">View</a>
			</td>
		</tr>
		{% endfor %}
	</tbody>
</table>
