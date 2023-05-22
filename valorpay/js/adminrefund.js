$(document).ready(function () {
    
    if( $('input[type="submit"]').length > 0 && $('input[type="submit"]').val() == "Update" ) {
        $('#order_payment_edit').delegate('#refundbutton', 'click', function (e) {
            var vamount = $('input[name=orders_payment_amount]').val();
            if( !vamount ) {
                alert('Please enter valid amount.');
                $('input[name=orders_payment_amount]').focus();
                return;
            }
            else if( !$.isNumeric(vamount) ) {
                alert('Please enter valid amount.');
                $('input[name=orders_payment_amount]').focus();
                $('input[name=orders_payment_amount]').select();
                return;
            }
            $(this).prop('disabled',true);
            $('#order_payment_edit input[type="submit"]').parent().append('<div class="valorpay-container"><h2>One Time Code Verification</h2><h4 id="resp"></h4><div class="inputfield"><input type="number" maxlength="1" id="firstinput" class="input"/><input type="number" maxlength="1" class="input"/><input type="number"maxlength="1"class="input"/><input type="number"maxlength="1"class="input"/><input type="number"maxlength="1"class="input"/><input type="number"maxlength="1"class="input"/></div><p>Didn\'t receive code yet? <span id="timerbox"><br />Please wait <span id="timebox" style="padding-top:5px;" class="badge badge-primary">01.30</span> seconds to resend code</span> </p><p><button href="#" id="resendcode">Resend Code</button></p><div class="buttonbox"><input type="hidden" name="valorpay_uuid"><button id="otpsubmit" class="btn btn-primary">Submit</button> <button id="otpcancel" class="btn btn-default">Cancel</button></div> </div>;');
            $('#firstinput').focus();
            $("#otpsubmit").prop('disabled',true);
            $.fn.sendOtp();    
        });

        $.fn.timerCount = function() {
            var timer2 = "1:30";
            var interval = setInterval(function() {
                var timer = timer2.split(':');
                var minutes = parseInt(timer[0], 10);
                var seconds = parseInt(timer[1], 10);
                --seconds;
                minutes = (seconds < 0) ? --minutes : minutes;
                seconds = (seconds < 0) ? 59 : seconds;
                seconds = (seconds < 10) ? '0' + seconds : seconds;
                $('#timebox').html('0'+minutes + ':' + seconds);
                if (minutes < 0) clearInterval(interval);
                if ((seconds <= 0) && (minutes <= 0)) {
                    clearInterval(interval);
                    $('#timerbox').hide();
                    $("#resendcode").prop('disabled',false);
                    $("#resendcode").removeClass('disabledresendcode');
                }
                timer2 = minutes + ':' + seconds;
            }, 1000);
        }

        $.fn.sendOtp = function() {
            $.post(valorpay.sendotp_url, { platform_id: valorpay.platform_id, opyID: valorpay.opyID, amount: $('input[name=orders_payment_amount]').val() }, function( data ) {
                data = JSON.parse(data);
                if( data.message ) $("#resp").html(data.message);
                if( data.error ) $("#otpsubmit").prop('disabled',true);
                else {
                    $('input[name=valorpay_uuid]').val(data.uuid);
                    $.fn.timerCount();
                    $('#timerbox').show();
                    $("#resendcode").prop('disabled',true);
                    $("#resendcode").addClass('disabledresendcode');
                }
            });
        }

        $('#order_payment_edit').delegate('#resendcode', 'click', function (e) {
            e.preventDefault();
            $.each($('.input'), function (index, value) {
                $(this).val('');
            });
            $("#resp").html('');
            $("#otpsubmit").prop('disabled',true);
            $.fn.sendOtp();
        });

        $('#order_payment_edit').delegate('#otpsubmit', 'click', function (e) {
            $(this).prop('disabled',true);
            var fullstring = ''; 
            $.each($('.input'), function (index, value) {
                fullstring += $(this).val();
            });
            $.post(valorpay.refund_url, { otp: fullstring, uuid: $('input[name=valorpay_uuid]').val(), platform_id: valorpay.platform_id, opyID: valorpay.opyID, amount: $('input[name=orders_payment_amount]').val() }, function( data ) {
                data = JSON.parse(data);
                if( data.error && data.message ) {
                    $("#otpsubmit").prop('disabled',true);
                    $.each($('.input'), function (index, value) {
                        $(this).val('');
                    });
                    $("#resp").html(data.message);
                }
                else if( data.message ) {
                    $(".valorpay-container").html(data.message);
                    setTimeout(function() {
                        location.reload();
                    },5000);
                }
            });
        });

        $('#order_payment_edit').delegate('#otpcancel', 'click', function (e) {
            $(".valorpay-container").remove();
            $("#refundbutton").prop('disabled',false);
        });

        $('#order_payment_edit').delegate('#donebutton', 'click', function (e) {
            location.reload();
        });

        $.fn.balancepayment = function() {
            $.post(valorpay.balancepayment_url, { opyID: valorpay.opyID }, function( data ) {
                data = JSON.parse(data);
                if( !data.error && Number(data.amount) <= 0 ) {
                    $('#order_payment_edit input[type="submit"]').parent().append('<label class="btn btn-danger" style="float:left;">'+data.message+'</label>');
                    $('#order_payment_edit input[type="submit"]').hide();
                    $('textarea[name="orders_payment_transaction_commentary"]').val('');
                }
                else {
                    $('input[name=orders_payment_amount]').val(data.amount);
                    $('#order_payment_edit input[type="submit"]').parent().append('<input type="button" class="btn btn-primary" id="refundbutton" value="Refund via ValorPay" style="float:left;">');
                    $('textarea[name="orders_payment_transaction_commentary"]').val('');
                    $('#order_payment_edit input[type="submit"]').show();
                }
            });
        }

        $.fn.checkAllBox = function() {
            var fullstring = ''; 
            $.each($('.input'), function (index, value) {
                fullstring += $(this).val();
            });
            if( fullstring.length == 6 ) {
                $("#otpsubmit").prop('disabled',false);
            }
            else {
                $("#otpsubmit").prop('disabled',true);
            }
        }

        $('#order_payment_edit').delegate('.input', 'input', function (e) {
            $(this).val(
                $(this)
                    .val()
                    .replace(/[^0-9]/g, "").substr(0,1)
            );
            $.fn.checkAllBox();
        });
        
        $('#order_payment_edit').delegate('.input', 'keyup', function (e) {
            let key = e.keyCode || e.charCode;
            if (key == 8 || key == 46 || key == 37 || key == 40) {
                // Backspace or Delete or Left Arrow or Down Arrow
                $(this).prev().focus();
            } else if (key == 38 || key == 39 || $(this).val() != "") {
                // Right Arrow or Top Arrow or Value not empty
                $(this).next().focus();
            }
        });

        $('#order_payment_edit').delegate('.input', 'paste', function (e) {
            var obj = $('.input');
            var paste_data = e.originalEvent.clipboardData.getData("text");
            var paste_data_splitted = paste_data.split("");
            $.each(paste_data_splitted, function (index, value) {
                obj.eq(index).val(value);
            });
        });

        setTimeout(function(){
            $.fn.balancepayment();
        }, 500);

    }

});