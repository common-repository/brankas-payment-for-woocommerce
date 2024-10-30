function setBrankasPaymentSource(paymentMethod) {
    let paymentSrcElement = document.getElementById('brankas_payment_source');
    if(typeof(paymentSrcElement) != 'undefined' && paymentSrcElement != null) {
        paymentSrcElement.value = paymentMethod.getAttribute('data-payment-source')
    }
}

function updateBrankasPaymentSource() {
    jQuery(document).ready(function($){
        var selectedPaymentMethod = $('input[name=\"payment_method\"]:checked').val();
        if(selectedPaymentMethod != 'brankas'){
            return;
        }
        if($('#brankas_payment_source').length > 0){
            return;
        }
        $('#payment').append('<input type=\"hidden\" id=\"brankas_payment_source\" name=\"payment_source\"  />');
        var paymentSource = $('#brankas_payment_source').val;
        if(!$.isNumeric(paymentSource)) {
            var selectedPaymentSource = $('input[name=\"payment_method\"]:checked').attr('data-payment-source');
            $('#brankas_payment_source').val(selectedPaymentSource);
        }

    });
}