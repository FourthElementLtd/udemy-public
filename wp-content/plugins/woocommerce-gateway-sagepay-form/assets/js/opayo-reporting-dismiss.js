jQuery(document).on( 'click', '.setup-opayo-reporting-notice .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'dismiss_setup_opayo_reporting_notice'
        }
    });

})