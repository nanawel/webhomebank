const path = require('path');

module.exports = {
    mode: "development",
    entry: './js/app.js',
    output: {
        path: __dirname + '/js',
        filename: 'app.bundle.js',
    },
};
