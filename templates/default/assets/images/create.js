$(function() {
	$('#formcontrols button[data-action="saveimage"]').removeClass('hidden');
	setEditable($('table#imageinfo td[data-name]'));
	$('.editonly').show();

	$('button[data-action="editimage"]').remove();
});
