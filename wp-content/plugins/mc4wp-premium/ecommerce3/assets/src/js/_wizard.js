const i18n = mc4wp_ecommerce.i18n;
const m = require('mithril');

function a(mount, type, ids) {
	let running = false;
	let current = 0;
	let progress = 0.00;
	let done = ids.length === 0;
	let log = [];
	let statusLabel = mount.parentElement.querySelector('.mc4wp-status-label');

	const tick = () => {
		m.request({
			method: 'POST',
			url: window.ajaxurl + `?action=mc4wp_ecommerce_sync_${type}`,
			body: ids.slice(current, current + 10)
		}).then((results) => {
			current += results.length
			progress = current / ids.length;

			// add results to log
			log.push({
				date: new Date(),
				messages: results,
			})

			// keep going or finish
			if (current < ids.length) {
				tick();
			} else {
				running = false;
				done = true;
			}
		})
	}

	const toggle = function() {
		statusLabel.parentNode.removeChild(statusLabel);

		running = !running;
		if (!running) {
			return;
		}

		tick();
	}

	const scrollToBottom = function(vnode) {
		vnode.dom.scrollTop = vnode.dom.scrollHeight;
	}

	return {
		view: function() {
			return m('div', [
					m('p', [
						m('button.button', { onclick: toggle, disabled: done }, done ? 'All done!' : running ? 'Stop' : 'Start'),
						" ",
						(running ? m('span.description', (progress * 100).toFixed(2) + '% — Please wait... This can take a while if you have many ' + type + '.') : ''),
					]),
					m('div.mc4wp-margin-m', { style: log.length > 0 ? 'display: block;' : 'display: none;' }, [
						m('div.results', { style: 'max-height: 240px; overflow-y: scroll;', oncreate: scrollToBottom, onupdate: scrollToBottom }, log.map(l => m('div.mc4wp-margin-s', [
							m('div', [ m('strong', l.date.toLocaleString() )]),
							l.messages.map(msg => m('div', msg)),
						]))),
						m('p', [
							m('a', { href: '', onclick: (evt) => { evt.preventDefault(); log = []; } }, 'Clear results'),
						])
					])
				]);
		}
	}
}

[].forEach.call(document.querySelectorAll('[data-wizard]'), (mount) => {
	let type = mount.getAttribute('data-wizard'),
		objectIds = JSON.parse(mount.getAttribute('data-object-ids'));

	m.mount(mount, a(mount, type, objectIds));
})


