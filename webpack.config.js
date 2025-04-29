const path = require('path');

module.exports = {
  mode: 'production',
  entry: {
    'tesseract-bundle': './src/js/tesseract-wrapper.js'
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'public/js/vendor'),
    library: {
      name: 'TesseractWrapper',
      type: 'window',
      export: 'default'
    }
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      }
    ]
  },
  resolve: {
    fallback: {
      "fs": false,
      "path": false,
      "crypto": false,
      "stream": false
    }
  }
};