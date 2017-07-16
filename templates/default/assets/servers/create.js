$(function() {
	$.getJSON("{{ url('/images.json') }}", function( data ) {
		setImageData(data);

		$('#formcontrols button[data-action="saveserver"]').removeClass('hidden');
		setEditable($('table#serverinfo td[data-name]'));
		$('button[data-action="editserver"]').remove();
	});

});
