'use strict';

const Lucy = require('./third-party/lucy.js');
const config = {
	algoliaAppId: 'CGLHJ0181U',
	algoliaAppKey: '8fa2f724a6314f9a0b840c85b05b943e',
	algoliaIndexName: 'mc4wp_kb',
	links: [
		{
			text: "<span class=\"dashicons dashicons-book\"></span> Knowledge Base",
			href: "https://www.mc4wp.com/kb/"
		},
		{
			text: "<span class=\"dashicons dashicons-editor-code\"></span> Code Snippets",
			href: "https://github.com/ibericode/mc4wp-snippets"
		},
		{
			text: "<span class=\"dashicons dashicons-editor-break\"></span> Changelog (free plugin)",
			href: "https://wordpress.org/plugins/mailchimp-for-wp/#developers"
		},
		{
			text: "<span class=\"dashicons dashicons-editor-break\"></span> Changelog (Premium plugin)",
			href: "https://my.mc4wp.com/plugins/1/changelog"
		}
	],
	contactLink: 'mailto:support@mc4wp.com'
};

// grab from WP dumped var.
if( window.lucy_config ) {
	config.contactLink = window.lucy_config.email_link;
}

new Lucy(
	config.algoliaAppId,
	config.algoliaAppKey,
	config.algoliaIndexName,
	config.links,
	config.contactLink
);
