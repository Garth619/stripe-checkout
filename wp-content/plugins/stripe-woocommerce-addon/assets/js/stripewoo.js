Stripe.setPublishableKey( stripe_array.stripe_publishablekey );

jQuery( function ( $ ) {
    var $form = $( 'form.checkout,form#order_review'),$stripeform, $stripe_cardno, $stripe_expiry, $stripe_cvc;
    
    function initcreditcardform () {
        $stripeform    = $( '#stripe-cc-form' );
        $stripe_cardno = $stripeform.find( '#stripe-card-number' );
        $stripe_expiry = $stripeform.find( '#stripe-card-expiry' );
        $stripe_cvc    = $stripeform.find( '#stripe-card-cvc' );

    }

    function stripeFormHandler () {
        if ( $( '#payment_method_stripe' ).is( ':checked' )  ) {
        	
            if ( ! $( 'input.stripe_token' ).length ) {
                var cardexpiry = $stripe_expiry.payment( 'cardExpiryVal' ),
                stripebillingname = ( $( '#billing_first_name' ).val() || $( '#billing_last_name' ).val() ) ? $( '#billing_first_name' ).val() + ' ' + $( '#billing_last_name' ).val() : stripe_array.billing_name;

                var stripedata = {
                    number          : $stripe_cardno.val() || '',
                    cvc             : $stripe_cvc.val() || '',
                    exp_month       : cardexpiry.month || '',
                    exp_year        : cardexpiry.year || '',
                    name            : stripebillingname || '',
                    address_line1   : $( '#billing_address_1' ).val() || stripe_array.billing_address_1 || '',
                    address_line2   : $( '#billing_address_2' ).val() || stripe_array.billing_address_2 || '',
                    address_city    : $( '#billing_city' ).val()      || stripe_array.billing_city || '',
                    address_state   : $( '#billing_state' ).val()     || stripe_array.billing_state || '',
                    address_zip     : $( '#billing_postcode' ).val()  || stripe_array.billing_postcode || '',
                    address_country : $( '#billing_country' ).val()   || stripe_array.billing_country || ''
                };

                // Validate form fields, create token if form is valid


                $('#place_order').prop('disabled', true);
                if ( validateCardForm( stripedata ) ) {    
                    Stripe.createToken( stripedata, stripeResponseHandler );

                    return false;
                }

            }
        }
        $('#place_order').prop('disabled', false);
        return true;
    }

    function stripeResponseHandler ( status, response ) {

        if ( response.error ) {
            $( '.stripe_token, .payment-errors' ).remove();
            $stripeform.before( '<span class="payment-errors required">' + response.error.message + '</span>' );
            $('#place_order').prop('disabled', false);
        } else {
            $form.append( '<input type="hidden" class="stripe_token" name="stripe_token" value="' + response.id + '"/>' );
            $('#stripe-card-number').val(''); 
            $('#stripe-card-expiry').val(''); 
            $('#stripe-card-cvc').val('');
            $form.submit();
        }

        $( '.stripe_token, .payment-errors' ).remove();
    }

    function validateCardForm ( stripedata ) {
       var errors = validateCardDetails( stripedata ); 
       
        if ( errors.length ) {
            $( '.stripe_token, .payment-errors' ).remove();
            if(errors.length > 0){
                for(var i = 0, l = errors.length; i < l; i++){
                    $stripeform.before('<label class="payment-errors required">' + errors[i] + '</label><br class="payment-errors">') ;
                }
            }
           // $form.append( '<input type="hidden" class="form_errors" name="form_errors" value="1">' );
            return false;
        }
        else 
        {
            $form.find( '.woocommerce-error' ).remove();   
            return true;
        }
    }


        function validateCardDetails(stripedata){
        var errors  = [];
        
        var validCardNumber = $.payment.validateCardNumber(stripedata.number);
        var validCardCVC    = $.payment.validateCardCVC(stripedata.cvc);
        var validCardExpiry = $.payment.validateCardExpiry(stripedata.exp_month, stripedata.exp_year);

        if(! validCardNumber){
            errors.push("Enter a valid card number");
        }
        if(! validCardCVC){
            errors.push("Enter a valid cvc number");
        }
        if(! validCardExpiry){
            errors.push("Enter a valid expiry date");
        }
        return errors;
    
    };

    $( 'body' ).on( 'updated_checkout.stripe', initcreditcardform ).trigger( 'updated_checkout.stripe' );
    $( 'form.checkout' ).on( 'checkout_place_order', stripeFormHandler );
    $( 'form#order_review' ).on( 'submit', stripeFormHandler );

});