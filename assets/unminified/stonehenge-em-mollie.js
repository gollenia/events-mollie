jQuery.noConflict();
(function($) {
$(document).ready(function(){

	if( $('#show_methods_no').is(':checked') ) {
		$('.mollie-refresh-methods').hide();
	} else {
		$('.mollie-refresh-methods').show();
	}
	$('[name="em_mollie_show_methods"]').on('click', function() {
		$('.mollie-refresh-methods').toggle(500);
	});

	if( $('#show_status_no').is(':checked') ) {
		$('.mollie-status-text').hide();
	} else {
		$('.mollie-status-text').show();
	}
	$('[name="em_mollie_show_status"]').on('click', function() {
		$('.mollie-status-text').toggle(500);
	});

});
})
(jQuery);
