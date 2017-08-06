$(function() {
	$('#formcontrols button[data-action="saveimage"]').removeClass('hidden');
	setEditable($('table#imageinfo td[data-name]'));
	$('.table-sortable').sortable("enable");
	$('.editonly').show();

	$('button[data-action="editimage"]').remove();
});
