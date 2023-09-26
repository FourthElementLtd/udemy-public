import Chart from 'chart.js';

const colors = [ "#cc3333", "#993333", "#ffcccc", "#ff9999", "#33cc33", "#339933", "#ccffcc", "#99ff99" ]
const datasets = window.mc4wp_statistics_data.map((s, i) => {
	s.fill = false;
	s.borderColor = colors[i] || getRandomColor();
	s.backgroundColor = colors[i] || getRandomColor();
	return s;
})
const dateRangeSelectorElement = document.getElementById('mc4wp-graph-range')
const customRangeOptionsElement = document.getElementById('mc4wp-graph-custom-range-options')

function getRandomColor() {
	const letters = '0123456789ABCDEF'.split('');
	let color = '#';
	for (let i = 0; i < 6; i++ ) {
		color += letters[Math.floor(Math.random() * 16)];
	}
	return color;
}

function plotGraph() {
	if (datasets.length === 0) {
		return;
	}

	const chartWrapperEl = document.getElementById('mc4wp-graph');
	const ctx = chartWrapperEl.getContext('2d')
	new Chart(ctx, {
		type: 'bar',
		data: {
			datasets: datasets,
		},
		options: {
			animation: {
				duration: 0 // disable animations
			},
			layout: {
				padding: {
					left: 20,
					right: 20,
					top: 20,
					bottom: 20
				}
			},
			scales: {
				x: {
					stacked: true,
				},
				y: {
					beginAtZero: true,
					stacked: true,
					suggestedMax: 10,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});
}

dateRangeSelectorElement.addEventListener('change', (evt) => {
	customRangeOptionsElement.style.display = evt.target.value === 'custom' ? '' : 'none';
});

document.addEventListener('DOMContentLoaded', plotGraph);
