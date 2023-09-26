<script type="text/javascript" language="javascript">

    var browserUserAgent = function () {
        return (navigator.userAgent || null);
    };

    var browserLanguage = function () {
        return (navigator.language || navigator.userLanguage || navigator.browserLanguage || navigator.systemLanguage || 'en-gb');
    };

    var browserColorDepth = function () {
    	var acceptedValues = [1,4,8,15,16,24,32,48];
        if (screen.colorDepth || window.screen.colorDepth) {

            colorDepth = (screen.colorDepth || window.screen.colorDepth);
            var returnValue = acceptedValues.indexOf( colorDepth );

            if( returnValue >= 0 ) {
            	return colorDepth;
            }

            // Fallback	
            return 32;
            
        }
        return 32;
    };

    var browserScreenHeight = function () {
        if (window.screen.height) {
            return new String(window.screen.height);
        }
        return null;
    };

    var browserScreenWidth = function () {
        if (window.screen.width) {
            return new String(window.screen.width);
        }
        return null;
    };

    var browserTZ = function () {
        return new String(new Date().getTimezoneOffset());
    };

    var browserJavaEnabled = function () {
        return (navigator.javaEnabled() || null);
    };

    var browserJavascriptEnabled = function () {
        return (true);
    };

	var sageform = document.getElementById( "sagepaydirect-cc-form" );

	function createHiddenInput( form, name, value ) {

		var input = document.createElement("input");
		input.setAttribute( "type", "hidden" );
		input.setAttribute( "name", name ); 
		input.setAttribute( "value", value );
		form.appendChild( input);

	}

	if ( sageform != null ) {

        createHiddenInput( sageform, 'browserJavaEnabled', browserJavaEnabled() );
        createHiddenInput( sageform, 'browserJavascriptEnabled', browserJavascriptEnabled() );
        createHiddenInput( sageform, 'browserLanguage', browserLanguage() );
        createHiddenInput( sageform, 'browserColorDepth', browserColorDepth() );
        createHiddenInput( sageform, 'browserScreenHeight', browserScreenHeight() );
        createHiddenInput( sageform, 'browserScreenWidth', browserScreenWidth() );
        createHiddenInput( sageform, 'browserTZ', browserTZ() );
        createHiddenInput( sageform, 'browserUserAgent', browserUserAgent() );

	}

</script>