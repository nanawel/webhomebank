const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    mode: "development",
    entry: ['./js/app.js', './scss/app.scss'],
    output: {
        path: __dirname + '/dist',
        filename: 'app.bundle.js',
    },
    resolve: {
        alias: {
            WhbDefaultTheme: path.resolve(__dirname, '../default'),
        },
    },
    plugins: [new MiniCssExtractPlugin({filename: 'app.css'})],
    module: {
        rules: [
            {
                test: /\.s[ac]ss$/i,
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
                    "sass-loader",
                ],
            },
            {
                test: /\.(png|svg|jpg|jpeg|gif)$/i,
                type: 'asset/resource',
            },
        ],
    },
};
