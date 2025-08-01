module.exports = {
  base: {
    exclude: [
      'html',
      'html-starter',
      'html-demo',
      'dist',
      'build',
      'assets',
      'tasks',
      'node_modules',
      '_temp',
      'fonts'
    ],
    serverPath: './',
    buildTemplatePath: 'html/vertical-menu-template',
    buildPath: './build'
  },
  development: {
    distPath: './assets/vendor',
    buildTemplatePath: 'html/vertical-menu-template',
    minify: false,
    sourcemaps: false,
    devtool: 'eval-source-map',
    cleanDist: true
  },
  production: {
    distPath: './assets/vendor',
    minify: true,
    sourcemaps: false,
    devtool: '#source-map',
    cleanDist: true
  }
};