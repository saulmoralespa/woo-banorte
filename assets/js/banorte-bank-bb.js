(function ($) {
    $(function() {
        if ($('#banorte-bank').is(":visible"))
        {
            new Card({
                form: document.querySelector('#banorte-bank'),
                container: '.card-wrapper'
            });
        }

        let msjerror = $('#card-banorte-bank-bb .msj-error-banorte ul strong li');
        let mjsjwait = $('#card-banorte-bank-bb .overlay').children('div');

        $('input[name="CARD_NUMBER"]').keyup(function () {
            if (!checkCard()){
                $(msjerror).parents( ".msj-error-banorte" ).show();
                $(msjerror).text(banorteBankWoo.msjTypeCard);
            }else{
                $(msjerror).parents( ".msj-error-banorte" ).hide();
            }
        })

        $('form#banorte-bank').submit(function (e) {
            e.preventDefault()

            $(msjerror).parents( '.msj-error-banorte' ).hide();
            $("input[type=submit]").attr('disabled','disabled');

            let ccNumber = $('input[name="CARD_NUMBER"]')
            let valNumbercc = $(ccNumber).val()

            if (!valid_credit_card(valNumbercc)){
                $(msjerror).parents( ".msj-error-banorte" ).show();
                $(msjerror).text(banorteBankWoo.msjvalidCard);
                $(ccNumber).focus()
                return
            }

            if (!checkCard()){
                $(msjerror).parents( ".msj-error-banorte" ).show();
                $(msjerror).text(banorteBankWoo.msjTypeCard);
                return
            }

            let card_exp = $('input[name="CARD_EXP"]')
            let exp = $(card_exp).val()
            exp = exp.replace(/\s/g,'').split('/')

            let day
            let year
            let date_end

            if (exp[1]){
                day = exp[0]
                year = exp[1]
                date_end = day.toString() + year
            }else{
                date_end = exp
            }

            if (year && year.length === 4){
                let year_end = year.substr(-2).toString()
                date_end = day + year_end
            }
            $(card_exp).val(date_end)
            $(ccNumber).val(valNumbercc.replace(/\s/g,''))

            $('#card-banorte-bank-bb ').css('cursor', 'wait')
            $('#card-banorte-bank-bb  .overlay').show()
            mjsjwait.text(banorteBankWoo.redirect);

            window.location.replace($(this).attr('action') + '?' + $(this).serialize())

        })

        function checkCard(){
            let classCard = $(".jp-card-identified" ).attr( "class" )
            if (!classCard) return
            switch(true) {
                case (classCard.indexOf('visa') !== -1):
                    return true;
                    break;
                case (classCard.indexOf('mastercard') !== -1):
                    return true;
                    break;
                default:
                    return false;
            }
        }


    });
})(jQuery)


function valid_credit_card(value) {
    // accept only digits, dashes or spaces
    if (/[^0-9-\s]+/.test(value)) return false;
    // The Luhn Algorithm. It's so pretty.
    var nCheck = 0, nDigit = 0, bEven = false;
    value = value.replace(/\D/g, "");
    for (var n = value.length - 1; n >= 0; n--) {
        var cDigit = value.charAt(n),
            nDigit = parseInt(cDigit, 10);
        if (bEven) {
            if ((nDigit *= 2) > 9) nDigit -= 9;
        }
        nCheck += nDigit;
        bEven = !bEven;
    }
    return (nCheck % 10) == 0;
}