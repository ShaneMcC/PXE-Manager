<H1>Bootable Images :: Import</H1>

<form method="post" id="imageform" action="{{ url("#{pathprepend}/images/import.json") }}">
	<input type="hidden" name="csrftoken" value="{{ csrftoken }}">
	<div class="form-group">
		<textarea rows="20" class="form-control mono" name="image" id="image">{{ image }}</textarea>
	</div>
	<div class="form-group">
		<button type="submit" data-action="importimage" class="btn btn-primary btn-block">Import</button>
	</div>
</form>

<script src="{{ url('/assets/images/import.js') }}"></script>
