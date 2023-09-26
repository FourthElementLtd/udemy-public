const Loader = require('./_form-loader.js');
let config = window.mc4wp_ajax_vars;
let busy = false;

function submit( form ) {
	const loader = new Loader(form.element, config['loading_character']);

	function start() {
		// Clear possible errors from previous submit
		form.setResponse('');
		loader.start();
		fire();
	}

	function fire() {
		busy = true;
		const request = new XMLHttpRequest();
		request.onreadystatechange = function() {
			if (request.readyState >= XMLHttpRequest.DONE) {
				clean();

				if (request.status >= 200 && request.status < 400) {
					try {
						let data = JSON.parse(request.responseText);
						process(data);
					} catch(error) {
						console.error( 'Mailchimp for WordPress: failed to parse response: "' + error + '"' );
						form.setResponse('<div class="mc4wp-alert mc4wp-error"><p>'+ config['error_text'] + '</p></div>');
					}
				} else {
					console.error('MailChimp for WordPress: request error: "' + request.responseText + '"');
				}
			}
		};
		request.open('POST', config['ajax_url'], true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		request.setRequestHeader('Accept', 'application/json');
		request.send(form.getSerializedData());
	}

	function process( response ) {
		trigger('submitted', [form, null]);

		if (response.error) {
			form.setResponse(response.error.message);
			trigger('error', [form, response.error.errors]);
		} else {
			const data  = form.getData();

			// trigger events
			trigger('success', [form, data]);
			trigger( response.data.event, [form, data]);

			// for BC: always trigger "subscribed" event when firing "updated_subscriber" event
			// third boolean parameter indicates this was a BC event
			if( response.data.event === 'updated_subscriber' ) {
                trigger('subscribed', [form, data, true]);
			}

			if( response.data.hide_fields ) {
				form.element.querySelector('.mc4wp-form-fields').style.display = 'none';
			}

			// show success message
			form.setResponse(response.data.message);

			// reset form element
			form.element.reset();

			// maybe redirect to url
			if( response.data.redirect_to ) {
				window.location.href = response.data.redirect_to;
			}
		}
	}

	function trigger(event, args) {
		window.mc4wp.forms.trigger(event, args);
		window.mc4wp.forms.trigger(args[0].id + "." + event, args);
	}

	function clean() {
		loader.stop();
		busy = false;
	}

	// let's do this!
	if( ! busy ) {
		start();
	}
}

// failsafe against including script twice
if ( typeof(window.mc4wp) === "undefined" ) {
	console.warn("Mailchimp for WordPress Premium: Unable to initialize AJAX forms feature because Mailchimp for WordPress core script is not propery loaded.");
} else if (! config['inited'] ) {
	window.mc4wp.forms.on('submit', function (form, event) {
		// does this form have AJAX enabled?
		if (form.element.getAttribute('class').indexOf('mc4wp-ajax') < 0) {
			return;
		}

		// blur active input field
		if (document.activeElement && document.activeElement.tagName === 'INPUT') {
			document.activeElement.blur();
		}

		try {
			submit(form);
		} catch (e) {
			console.error(e);
			return true;
		}

		event.returnValue = false;
		event.preventDefault();
		return false;
	});

	config['inited'] = true;
}
