const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    mode: "development",
    entry: './js/app.js',
    output: {
        path: __dirname + '/dist',
        filename: 'app.bundle.js',
    },
    plugins: [new MiniCssExtractPlugin({filename: 'app.css'})],
    module: {
        rules: [
            {
                test: /\.css$/i,
                use: [
                    "style-loader",
                    MiniCssExtractPlugin.loader,
                    {
                        loader: "css-loader",
                        options: {
                            url: {
                                filter: (url, resourcePath) => {
                                    // Don't handle images under root-relative /external_images/
                                    if (/^\.\.\/images\//.test(url)) {
                                        return false;
                                    }
                                    return true;
                                },
                            }
                        }
                    },
                ],
            },
            {
                test: /\.(png|svg|jpg|jpeg|gif)$/i,
                type: 'asset/resource',
            },
        ],
    },
};
