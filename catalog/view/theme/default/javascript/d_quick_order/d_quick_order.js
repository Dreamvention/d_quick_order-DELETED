var prefix = 'quickorder-';
var form_selector = '#quickorder_form';
var title_selector = '#dqo_title';
var separator = '#dqo-separator';
var alert_selector = '.alert';
var phone_selector = '.dqo_phone';
var product_id_selector = '#d_qo_product_id';
var input_qty_selector = '#input-quantity';
var dqo_badge_selector = '#badge_selector';
var product_amount_selector = '#product_amount';
var submit_button_selector = '#dqo_submit';
var submit_button_text = "Quick Order Now!";

$(document).on('submit', form_selector, function (e) {
    e.preventDefault();
    e.stopPropagation();

    var form = this;

    var showMessage = function (html) {
        if ($(form).find(separator).length) {
            $(form).find(separator).after(html);
        } else {
            $(form).prepend(html);
        }
    };

    var showError = function (message) {
        var html = '<div class="dqo-alert alert alert-danger"><i class="fa fa-warning"></i> ' + message + '<button type="button" class="close dqo-close" data-dismiss="alert"><i class="fa fa-times"></button></div>';

        showMessage(html);
    };

    var showSuccess = function (message) {
        var html = '<div class="dqo-alert alert alert-success success"><i class="fa fa-check"></i> ' + message + '<button type="button" class="close dqo-close" data-dismiss="alert"><i class="fa fa-times"></button></div>';
        showMessage(html);
    };

    submit_button_text = $(form).find(submit_button_selector).text();

    $.ajax({
        url: $(form).attr('action'),
        data: $(form_selector).serialize(),
        type: 'POST',
        dataType: 'json',
        beforeSend: function () {
            $(form).find(alert_selector).remove();
            $(form).find(submit_button_selector).text('loading').attr("disabled", true);
        },
        success: function (data) {
            if (data.error) {
                showError(data.error);
            } else if (data.success) {
                showSuccess(data.success);
                $(form).find(submit_button_selector).text(submit_button_text).attr("disabled", false);

                // clean modal
            } else if (data.redirect) {
                document.location = data.redirect;
            }
        },
        error: function () {
            showError('An unknown error has occurred.');
        },
        complete: function () {
            $(form).find(submit_button_selector).text(submit_button_text).attr("disabled", false);
        }
    });

    $(form).find(submit_button_selector).text(submit_button_text).attr("disabled", false).fadeIn();
});
