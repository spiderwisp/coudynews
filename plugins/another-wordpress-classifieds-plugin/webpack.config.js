/**
 * External dependencies
 */
const path = require( 'path' );

// Webpack configuration
const config = {
	mode: 'production',
	devtool: process.env.NODE_ENV !== 'production' ? 'source-map' : undefined,
	resolve: {
		extensions: [ '.json', '.js', '.jsx' ],
		modules: [ `${ __dirname }/js`, 'node_modules' ],
	},
	entry: {
		'onboarding-wizard': './resources/js/onboarding-wizard/index.js',
	},
	output: {
		filename: '[name].js',
		path: path.resolve( __dirname, 'resources/js' ),
	},
	module: {
		rules: [
			{
				test: /.js$/,
				exclude: /node_modules/,
				include: /js/,
				use: [
					{
						loader: 'babel-loader',
					},
				],
			},
			{
				test: /\.svg$/,
				use: [ '@svgr/webpack' ],
			},
			{
				test: /\.css$/i,
				use: [ { loader: 'style-loader' }, 'css-loader' ],
			},
		],
	},
	externals: {
		jquery: 'jQuery',
		$: 'jQuery',
	},
};

module.exports = config;
