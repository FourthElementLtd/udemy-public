jQuery(document).on( 'click', '.sagepaydirect-ssl-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_sagepaydirect_ssl_nag'
        }
    })

})

jQuery(document).on( 'click', '.sagepaydirect-cctype-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_sagepaydirect_cctype_nag'
        }
    });

})

jQuery(document).on( 'click', '.sagepaydirect-cookie-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_sagepaydirect_cookie_nag'
        }
    });

})

jQuery(document).on( 'click', '.opayo-rebrand-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_opayo_rebrand_nag'
        }
    });

})

jQuery(document).on( 'click', '.sagepaydirect-protocol4-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_sagepaydirect_protocol4_nag'
        }
    });

})

jQuery(document).on( 'click', '.sagepaydirect-threeds2-nag .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_sagepaydirect_threeds2_nag'
        }
    });

})