const cookie = require('./_cookie.js');

function getUrlValue(key) {
	const regex = new RegExp(key+'=([^&]+)');
	const matches = regex.exec(window.location.search)
	if (matches && matches.length > 0) {
		return matches[1];
	}

	return '';
}

// set mc_cid, mc_eid & mc_tc cookies if url params are set
[ 'mc_cid', 'mc_eid', 'mc_tc' ].forEach((name) => {
	const value = getUrlValue(name);
	if (value !== '') {
		cookie.create(name, value, 14);
	}
})

// store landing site in mc_landing_site cookie, if not set
if (!cookie.read('mc_landing_site')) {
	cookie.create('mc_landing_site', window.location.href, 7);
}

