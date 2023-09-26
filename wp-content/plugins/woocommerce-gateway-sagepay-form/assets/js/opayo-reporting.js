( function( $ ) {

	$( function() {

		$(document).ready(function(){

		  $( '.opayo_reporting_show_hide' ).hide();

		  $(".opayo_reporting_toggle").click(function(){

		    $( '.opayo_reporting_show_hide' ).toggle();
		    return false;

		  });

		});

	});

})( jQuery );