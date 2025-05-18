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
const populateChartWithData = function (chart, data) {
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

const whbChartjs = {
    Chart,
    populateBarChartWithData,
    populateChartWithData,
    populateDoughnutChartWithData,
}

window.whbChartjs = whbChartjs;

export default whbChartjs;
