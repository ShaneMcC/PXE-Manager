<H1>Server :: {{ server.name }} :: Duplicate</H1>

<form id="duplicateServerForm" method="post" action="{{ url('/servers/' ~ server.id ~ '/duplicate.json') }}">
	<input type="hidden" name="csrftoken" value="{{ csrftoken }}">
	<div class="form-group row">
		<label for="newname" class="col-4 col-form-label">Name</label>
		<div class="col-8">
			<input class="form-control" type="text" value="{{ server.name }} (Duplicate)" id="newname" name="newname">
		</div>
	</div>

	<div class="form-group row">
		<label for="newmac" class="col-4 col-form-label">MAC Address</label>
		<div class="col-8">
			<input class="form-control" type="text" value="" id="newmac" name="newmac">
		</div>
	</div>
</form>

<a href="{{ url('/servers') }}" data-action="cancel" class="btn btn-primary" data-dismiss="modal">Cancel</a>
<a href="#" data-action="ok" class="btn btn-success">Duplicate</a>

<script src="{{ url('/assets/servers/duplicate.js') }}"></script>
