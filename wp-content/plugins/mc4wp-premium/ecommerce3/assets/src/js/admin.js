const m = require('mithril');
const QueueProcessor = require('./_queue-processor.js');

// ask for confirmation for elements with [data-confirm] attribute
require('./_confirm-attr.js')();

// initial sync wizards for orders and products
require('./_wizard.js');


// queue processor
const queueRootElement = document.getElementById('queue-processor');
if (queueRootElement) {
    m.mount(queueRootElement, QueueProcessor);
}
