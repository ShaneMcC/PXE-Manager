<H1>Bootable Image :: {{ image.name }} :: Duplicate</H1>

<form id="duplicateImageForm" method="post" action="{{ url('/images/' ~ image.id ~ '/duplicate.json') }}">
	<input type="hidden" name="csrftoken" value="{{ csrftoken }}">
	<div class="form-group row">
		<label for="newname" class="col-4 col-form-label">Name</label>
		<div class="col-8">
			<input class="form-control" type="text" value="{{ image.name }} (Duplicate)" id="newname" name="newname">
		</div>
	</div>
</form>

<a href="{{ url('/images/') }}" data-action="cancel" class="btn btn-primary" data-dismiss="modal">Cancel</a>
<a href="#" data-action="ok" class="btn btn-success">Duplicate</a>

<script src="{{ url('/assets/images/duplicate.js') }}"></script>
