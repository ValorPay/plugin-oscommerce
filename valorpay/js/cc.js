$(document).ready(function () {
    
    $('form[name="one_page_checkout"]').submit(function (e) {
        hideErrorBoxes();
        var ccErrorFlag = true;
        var cc = new ValorPayCC();
    
        if (!cc.isValid($("#valorpay-card-number").val())) {
            $("#valorpay-card-number-error").fadeIn('slow');
            ccErrorFlag = false;
        }
        
        if (!cc.isExpirationDateValid($("#valorpay-card-expiry-month").val(), $("#valorpay-card-expiry-year").val())) {
            $("#valorpay-card-expiry-error").fadeIn('slow');
            ccErrorFlag = false;
        }

        if ($("#valorpay-card-name").val().length <= 0) {
            $("#valorpay-card-name-error").fadeIn('slow');
            ccErrorFlag = false;
        }
        
        if (!cc.isSecurityCodeValid($("#valorpay-card-number").val(), $("#valorpay-card-cvv").val())) {
            $("#valorpay-card-cvv-error").fadeIn('slow');
            ccErrorFlag = false;
        }

        if ($("#valorpay-card-address").length > 0 && $("#valorpay-card-address").val().length <= 0) {
            $("#valorpay-card-address-error").fadeIn('slow');
            ccErrorFlag = false;
        }

        if ($("#valorpay-card-zip").length > 0 && $("#valorpay-card-zip").val().length <= 0) {
            $("#valorpay-card-zip-error").fadeIn('slow');
            ccErrorFlag = false;
        }

        if (!ccErrorFlag) {
            $('.w-checkout-continue-btn button[type=submit]').prop('disabled', false);
            $('.w-checkout-continue-btn button[type=submit]').addClass('disabled-area');
            if( $('.hide-page').length > 0 ) $('.hide-page').remove();
            if( $('.fake-input').length > 0 ) $('.fake-input').remove();
            return ccErrorFlag;
        }
        return true;
    });

    window.ValorPayCreateCCForm = function() 
    {

        if ( $('input[name=payment][value="valorpay"]').length <= 0 ) return;

        if ($('input[name=payment][value="valorpay"]').is(':checked')){
            
            if( $('#valorCcBox').length > 0 ) $('#valorCcBox').remove();
            var valorpay_avs_string = '';
            if( valorpay_avs_options == 'zipandaddress' ) valorpay_avs_string = '<div class="row"><div class="col-75"><label for="valorpay-card-address">Address <span class="required">*</span></label><input type="text" id="valorpay-card-address" name="valorpay-card-address" value="" maxlength="25"><div id="valorpay-card-address-error" class="required-message-wrap top-error-mes"><div class="required-message" style="">Please enter address for verification.</div></div></div><div class="col-25"><label for="valorpay-card-zip">Zip <span class="required">*</span></label><input type="text" id="valorpay-card-zip" name="valorpay-card-zip" value="" maxlength="6"><div id="valorpay-card-zip-error" class="required-message-wrap top-error-mes"><div class="required-message" style="">Please enter zip for verification.</div></div></div></div>';
            else if( valorpay_avs_options == 'address' ) valorpay_avs_string = '<div class="row"><div class="col-75"><label for="valorpay-card-address">Address <span class="required">*</span></label><input type="text" id="valorpay-card-address" name="valorpay-card-address" value="" maxlength="25"><div id="valorpay-card-address-error" class="required-message-wrap top-error-mes"><div class="required-message" style="">Please enter address for verification.</div></div></div></div>';
            else if( valorpay_avs_options == 'zip' ) valorpay_avs_string = '<div class="row"><div class="col-75"><label for="valorpay-card-zip">Zip <span class="required">*</span></label><input type="text" id="valorpay-card-zip" name="valorpay-card-zip" value="" maxlength="6"><div id="valorpay-card-zip-error" class="required-message-wrap top-error-mes"><div class="required-message" style="">Please enter zip for verification.</div></div></div></div>';
            var formString = '<div class="container"><div class="row"><div class="logobox">'+valorpay_logos+'</div></div><div class="row"><div class="col-75"><label for="valorpay-card-number">Credit Card Number <span class="required">*</span></label><input type="text" id="valorpay-card-number" name="valorpay-card-number" autocomplete="off" maxlength="23"><div id="valorpay-card-number-error" class="required-message-wrap top-error-mes"><div class="required-message" style="">Please enter a valid credit card number.</div></div></div><div class="col-25"><label for="valorpay-card-expiry-month">Expiration <span class="required">*</span></label><div class="row"><div class="col-50-1"><select id="valorpay-card-expiry-month" name="valorpay-card-expiry-month"></select></div><div class="col-50-2"><select id="valorpay-card-expiry-year" name="valorpay-card-expiry-year"></select></div></div> <div id="valorpay-card-expiry-error" class="required-message-wrap top-error-mes"><div class="required-message">Please select valid expiration date.</div></div> </div></div><div class="row"><div class="col-75"><label for="valorpay-card-name">Name on Card <span class="required">*</span></label><input type="text" id="valorpay-card-name" name="valorpay-card-name" autocomplete="off" maxlength="25"><div id="valorpay-card-name-error" class="required-message-wrap top-error-mes"><div class="required-message" style="">Please enter name on credit card.</div></div></div><div class="col-25"><label for="valorpay-card-cvv">CVV <span class="required">*</span></label><input type="password" maxlength="4" id="valorpay-card-cvv" name="valorpay-card-cvv" autocomplete="off"><div id="valorpay-card-cvv-error" class="required-message-wrap top-error-mes"><div class="required-message" style="">Please enter a valid CVV.</div></div></div></div>'+valorpay_avs_string+'</div>';
            $("<div/>").attr('id','valorCcBox').html(formString).appendTo($(".payment_class_valorpay").parents('.type-1')[1]);
        
            for ( var cc_month_counter in valorpay_cc_months ) {
                var cc_month_value = valorpay_cc_months[cc_month_counter][0];
                var cc_month_text = $("<div/>").html(valorpay_cc_months[cc_month_counter][1]).text();
        
                $('<option/>').val(cc_month_value).text(cc_month_text).appendTo($('#valorpay-card-expiry-month'));
            };
        
            for ( var cc_year_counter in valorpay_cc_years ) {
                var cc_year_value = valorpay_cc_years[cc_year_counter][0];
                var cc_year_text = valorpay_cc_years[cc_year_counter][1];
        
                $('<option/>').val(cc_year_value).text(cc_year_text).appendTo($('#valorpay-card-expiry-year'));
            };

            window.ValorPayAddCardDetection();
            hideErrorBoxes();
            
            setTimeout(function() {
                var address  = ($('#shipping_address-street_address').length>0?$('#shipping_address-street_address').val():valorpay_street_address);
                var postcode = ($('#shipping_address-postcode').length>0?$('#shipping_address-postcode').val():valorpay_postcode);
                $("#valorpay-card-address").val(address);
                $("#valorpay-card-zip").val(postcode);
            },1000);

        }
        else {
            $('#valorCcBox').remove();
        }
    
    }
    
    window.ValorPayAddCardDetection = function()
    {
        $('#valorpay-card-number').on('keydown',function(e){
            var deleteKeyCode = 8;
            var tabKeyCode = 9;
            var backspaceKeyCode = 46;
            if ((e.key>=0 && e.key<=9) ||
                 e.which === deleteKeyCode || // for delete key,
                    e.which === tabKeyCode || // for tab key   
                        e.which === backspaceKeyCode) // for backspace
            {
                return true;
            }
            else
            {
                return false;
            }
        });
        
        $('#valorpay-card-number').keyup(function() {
            checkErrors();
            var val = $(this).val();
            var newval = '';
            var cardNumber = val.replace(/\s/g, '');
            for(var i=0; i < cardNumber.length; i++) {
                if(i%4 == 0 && i > 0) newval = newval.concat(' ');
                newval = newval.concat(cardNumber[i]);
            }
            $(this).val(newval);
            var detector = new ValorPayBrandDetection();
            var brand = detector.detect(cardNumber);
            $('.valorpay-cc-logo').css('opacity','0.3');
            if (brand && brand != "unknown") {
                if($('#valorpay-cc-'+brand).length <= 0 ) {
                    alert(brand.toUpperCase()+" credit card is not accepted");
                    $('#valorpay-card-number').val("");
                }
                else {
                    $('#valorpay-cc-'+brand).css('opacity','1');
                }
            }
        });

        $('#valorpay-card-expiry-month').change(function (e) {
            checkErrors();
        });

        $('#valorpay-card-expiry-year').change(function (e) {
            checkErrors();
        });

        $('#valorpay-card-name').keyup(function (e) {
            checkErrors();
        });
        
        $("#valorpay-card-name").on("keydown", function(event){
            // Allow controls such as backspace, tab etc.
            var arr = [8,9,16,17,20,32,35,36,37,38,39,40,45,46];
            // Allow letters
            for(var i = 65; i <= 90; i++){
              arr.push(i);
            }
            // Prevent default if not in array
            if(jQuery.inArray(event.which, arr) === -1){
                return false;
            }
            else return true;
        });

        $('#valorpay-card-cvv').keyup(function (e) {
            checkErrors();
        });

        $('#valorpay-card-cvv').on('keydown',function(e){
            var deleteKeyCode = 8;
            var tabKeyCode = 9;
            var backspaceKeyCode = 46;
            if ((e.key>=0 && e.key<=9) ||
                 (e.which>=96 && e.which<=105)  || // for num pad numeric keys
                 e.which === deleteKeyCode || // for delete key,
                    e.which === tabKeyCode || // for tab key
                        e.which === backspaceKeyCode) // for backspace
            {
                return true;
            }
            else
            {
                return false;
            }
        });

        if( $('#valorpay-card-address').length > 0 ) {
            $('#valorpay-card-address').keyup(function (e) {
                checkErrors();
            });
        }

        if( $('#valorpay-card-zip').length > 0 ) {
            $('#valorpay-card-zip').keyup(function (e) {
                checkErrors();
            });

            $('#valorpay-card-zip').on('keydown',function(e){
                var deleteKeyCode = 8;
                var tabKeyCode = 9;
                var backspaceKeyCode = 46;
                if ((e.key>=0 && e.key<=9) ||
                     (e.which>=96 && e.which<=105)  || // for num pad numeric keys
                     e.which === deleteKeyCode || // for delete key,
                        e.which === tabKeyCode || // for tab key
                            e.which === backspaceKeyCode) // for backspace
                {
                    return true;
                }
                else
                {
                    return false;
                }
            });
        }

    }

    function checkErrors()
    {
        var allentered = true;
        if( $("#valorpay-card-number").val().length <= 0 ) allentered = false;
        if( $("#valorpay-card-expiry-month").val().length <= 0 ) allentered = false;
        if( $("#valorpay-card-expiry-year").val().length <= 0 ) allentered = false;
        if( $("#valorpay-card-name").val().length <= 0 ) allentered = false;
        if( $("#valorpay-card-cvv").val().length <= 0 ) allentered = false;
        if( $("#valorpay-card-address").length > 0 && $("#valorpay-card-address").val().length <= 0 ) allentered = false;
        if( $("#valorpay-card-zip").length > 0 && $("#valorpay-card-zip").val().length <= 0 ) allentered = false;    
        if( allentered ) {
            $('.w-checkout-continue-btn button[type=submit]').prop('disabled', false);
            $('.w-checkout-continue-btn button[type=submit]').removeClass('disabled-area');
        }
    }

    function hideErrorBoxes()
    {
        $("#valorpay-card-cvv-error").css('display', 'none');
        $("#valorpay-card-number-error").css('display', 'none');
        $("#valorpay-card-expiry-error").css('display', 'none');
        $("#valorpay-card-name-error").css('display', 'none');
        if( $("#valorpay-card-address-error").length > 0 ) $("#valorpay-card-address-error").css('display', 'none');
        if( $("#valorpay-card-zip-error").length > 0 ) $("#valorpay-card-zip-error").css('display', 'none');
    }

    if (typeof tl == 'function'){
        tl(function(){
            window.ValorPayCreateCCForm();
            try {
                checkout_payment_changed.set('window.ValorPayCreateCCForm');
            } catch (e ) {console.log(e ); }
        })
    }

});