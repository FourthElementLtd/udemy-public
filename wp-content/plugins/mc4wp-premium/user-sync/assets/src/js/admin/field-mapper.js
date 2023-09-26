const NAME_INDEX_REGEX = /\[(\d+)\]/;

function FieldMapper(context) {
	function addRow() {
		let row = context.querySelector('.field-map-row');
		let newRow = row.cloneNode(true);
		let userField = newRow.querySelector('.user-field');
		let mailchimpField = newRow.querySelector('.mailchimp-field');
		let idx = Math.round(Math.random() * 10000);
		userField.value = '';
		userField.setAttribute('name', userField.name.replace(NAME_INDEX_REGEX, '[' + idx + ']'));
		mailchimpField.value = '';
		mailchimpField.setAttribute('name', mailchimpField.name.replace(NAME_INDEX_REGEX, '[' + idx + ']'));
		row.parentElement.appendChild(newRow);
		setAvailableFields();
		return false;
	}

	function setAvailableFields() {
		let values = [].map.call(context.querySelectorAll('.mailchimp-field'), el => el.value);
		[].forEach.call(context.querySelectorAll('.mailchimp-field'), (el) => {
			let selected = el.value;
			[].forEach.call(el.querySelectorAll('option'), (opt) => {
				opt.disabled = opt.value === '' || (values.indexOf(opt.value) > -1 && selected !== opt.value);
			});
		});
	}

	context.querySelector('.add-row').addEventListener('click', addRow);
	context.addEventListener('click', (evt) => {
		if (evt.target.tagName === 'INPUT' && evt.target.className && evt.target.className.indexOf('remove-row') > -1) {
			evt.target.parentElement.parentNode.removeChild(evt.target.parentElement);
			setAvailableFields();
		}
	}, true);
	context.addEventListener('change', (evt) => {
		if (evt.target.tagName === 'SELECT' && evt.target.className.indexOf('mailchimp-field') > -1) {
			setAvailableFields();
		}
		}, true);

	setAvailableFields();
}

module.exports = FieldMapper;
