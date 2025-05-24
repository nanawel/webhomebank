import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    ScatterController,
    TimeScale,
    Tooltip,
} from "chart.js";
import "chartjs-adapter-moment";

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    ScatterController,
    TimeScale,
    Tooltip,
);
window.Chart = Chart;

// Helper function for compatibility with ChartJS 2+
const populateDoughnutChartWithData = function(chart, data) {
    chart.data.labels.splice(0, chart.data.labels.length);
    chart.data.datasets.splice(0, chart.data.datasets.length);

    let newLabels = [];
    let newDataset = {
        data: [],
        backgroundColor: [],
    };
    data.forEach((datapoint) => {
        newLabels.push(datapoint.label);
        newDataset.data.push(datapoint.value);
        newDataset.backgroundColor.push(datapoint.color);
    });

    chart.data.labels = newLabels;
    chart.data.datasets.push(newDataset);
    chart.update();
}

// Helper function for compatibility with ChartJS 2+
const populateBarChartWithData = function(chart, data) {
    chart.data.labels.splice(0, chart.data.labels.length);
    chart.data.datasets.splice(0, chart.data.datasets.length);

    if (data['datasets'].length > 1) {
        throw 'NOT IMPLEMENTED';
    }

    let newLabels = [];
    for (let j in data.labels) {
        let ts = data.labels[j] * 1000;
        newLabels.push(ts);
    }
    chart.data.labels = newLabels;

    for (let i in data['datasets']) {
        const currentDataset = data['datasets'][i];
        let newDataset = {
            label: currentDataset.label,
            data: [],
            borderColor: currentDataset.strokeColor,
            backgroundColor: currentDataset.fillColor,
            pointHitRadius: 3
        };
        for (let j in currentDataset.data) {
            newDataset.data.push(currentDataset.data[j]);
        }
        chart.data.datasets.push(newDataset);
    }
    chart.update();
}

// Helper function for compatibility with ChartJS 2+
const populateLineChartWithData = function (chart, data) {
    chart.data.datasets.splice(0, chart.data.datasets.length);

    for (let i in data['datasets']) {
        const currentDataset = data['datasets'][i];
        let newDataset = {
            label: currentDataset.label,
            data: [],
            borderColor: currentDataset.strokeColor,
            backgroundColor: currentDataset.fillColor,
            pointHitRadius: 3
        };
        for (let j in currentDataset.data) {
            let ts = currentDataset.data[j].x * 1000;
            newDataset.data.push({
                x: new Date(ts),
                y: currentDataset.data[j].y
            });
        }
        chart.data.datasets.push(newDataset);
    }
    chart.update();
}

/**
 * See https://www.chartjs.org/docs/4.4.9/samples/legend/html.html
 */
const getOrCreateLegendList = (chart, id) => {
    const legendContainer = document.getElementById(id);
    let listContainer = legendContainer.querySelector('ul');

    if (!listContainer) {
        listContainer = document.createElement('ul');
        listContainer.className = 'line-legend';

        legendContainer.appendChild(listContainer);
    }

    return listContainer;
};
const htmlLegendPlugin = {
    id: 'htmlLegend',
    afterUpdate(chart, args, options) {
        const ul = getOrCreateLegendList(chart, options.containerID);

        // Remove old legend items
        while (ul.firstChild) {
            ul.firstChild.remove();
        }

        if (!chart.data?.datasets.length) {
            return;
        }

        // Reuse the built-in legendItems generator
        const items = chart.options.plugins.legend.labels.generateLabels(chart);

        items.forEach(item => {
            const li = document.createElement('li');
            li.style.cursor = 'pointer';

            li.onclick = () => {
                const {type} = chart.config;
                if (type === 'pie' || type === 'doughnut') {
                    // Pie and doughnut charts only have a single dataset and visibility is per item
                    chart.toggleDataVisibility(item.index);
                } else {
                    chart.setDatasetVisibility(item.datasetIndex, !chart.isDatasetVisible(item.datasetIndex));
                }
                chart.update();
            };

            // Color box
            const boxSpan = document.createElement('span');
            boxSpan.style.background = item.fillStyle;
            boxSpan.style.borderColor = item.strokeStyle;
            boxSpan.style.borderWidth = item.lineWidth + 'px';
            boxSpan.style.display = 'inline-block';
            boxSpan.style.flexShrink = 0;
            boxSpan.style.height = '20px';
            boxSpan.style.marginRight = '10px';
            boxSpan.style.width = '20px';
            boxSpan.className = 'label-color';

            // Text
            const textContainer = document.createElement('span');
            textContainer.style.color = item.fontColor;
            textContainer.style.margin = 0;
            textContainer.style.padding = 0;
            textContainer.style.textDecoration = item.hidden ? 'line-through' : '';
            textContainer.className = 'label-text';

            const text = document.createTextNode(item.text);
            textContainer.appendChild(text);

            li.appendChild(boxSpan);
            li.appendChild(textContainer);
            ul.appendChild(li);
        });
    }
};

const whbChartjs = {
    Chart,
    populateBarChartWithData,
    populateLineChartWithData,
    populateDoughnutChartWithData,
    htmlLegendPlugin,
}

window.whbChartjs = whbChartjs;

export default whbChartjs;
