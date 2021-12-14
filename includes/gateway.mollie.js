// This prevents the WordPress error "Headers already sent in..."
$(document).bind('em_booking_gateway_add_mollie', function(event, response){
	if(response.result){
		var mollieForm = $('<form action="'+response.mollie_url+'" method="get" id="em-mollie-redirect-form"></form>');
		$.each( response.mollie_vars, function(index,value){
			mollieForm.append('<input type="hidden" name="'+index+'" value="'+value+'" />');
		});
		mollieForm.append('<input id="em-mollie-submit" type="submit" style="display:none" />');
		mollieForm.appendTo('body').trigger('submit');
	}
});