module.exports = {
    module: {
        rules: [
            {
                test: /\.css$/i,
                use: [
                    'postcss-loader'
                ],
            },
        ],
    },
};
