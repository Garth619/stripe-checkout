/**
 *
 */
jQuery( document ).ready( function ( jQuery ) {
	jQuery( "input[name='gform_payment_method']" ).on( 'click', gfpStripeToggleCreditCard );
} );

function gfpStripeToggleCreditCard() {

	var card = jQuery( this );

	if ( jQuery( "#gform_payment_method_creditcard" ).is( ":checked" ) ) {
		gfpStripeShowCreditCardFields();
	}
	else {
		gfpStripeHideCreditCardFields();
		gfpStripeSetCardNumber( card );
	}
}

function gfpStripeShowCreditCardFields() {
	var card_number = jQuery( '.gform_card_icon_container' ).next();
	var card_number_label = card_number.next();
	var card_exp_and_code = jQuery( '.ginput_cardextras' );
	var card_name = card_exp_and_code.next();

	card_number.fadeIn();
	card_number_label.fadeIn();
	card_exp_and_code.fadeIn();
	card_name.fadeIn();
}

function gfpStripeHideCreditCardFields() {
	var card_number = jQuery( '.gform_card_icon_container' ).next();
	var card_number_label = card_number.next();
	var card_exp_and_code = jQuery( '.ginput_cardextras' );
	var card_name = card_exp_and_code.next();

	card_number.fadeOut();
	card_number_label.fadeOut();
	card_exp_and_code.fadeOut();
	card_name.fadeOut();
}

function gfpStripeSetCardNumber( card ) {
	var form = card.closest( 'form' );
	var field_id = card.closest( 'li' ).attr( 'id' );
	field_id = field_id.split( '_' );
	field_id = field_id[2];
	var card_number = card.val();
	form.append( "<input type='hidden' name='input_" + field_id + ".1' value='" + card_number + "' />" );
	var card_type = jQuery( 'div.gform_payment_' + card_number ).text();
	card_type = card_type.trim();
	card_type = card_type.split( '(' );
	card_type = card_type[0].trim();
	form.append( "<input type='hidden' name='input_" + field_id + ".4' value='" + card_type + "' />" );
}