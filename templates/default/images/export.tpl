<H1>Bootable Images :: {{ image.name }} <small>[ID: {{ image.id }}]</small></H1>
<small><em><Strong>Last Modified:</Strong> {{ image.lastmodified | date }}</em></small><br><br>

<div class="form-group">
	<textarea rows="20" class="form-control mono">{{ image | json_encode }}</textarea>
</div>
