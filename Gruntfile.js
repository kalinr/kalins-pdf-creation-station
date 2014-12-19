module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    
    concat: {
      options: {
        separator: ';',
      },
      dist: {
        src: ['vendor/angular.js', 'vendor/angular-ui-sortable.js',  'vendor/ng-table.js', 'vendor/ui-bootstrap-tpls-0.11.2.js'],
        dest: 'vendor/allVendorsConcatenated.js',
      },
    },
    
    uglify: {
      options: {
        banner: '/*! \nKalin\'s PDF Creation Station <%= grunt.template.today("yyyy-mm-dd") %> \nThis JavaScript file contains: angular.js, ng-table.js, ui-bootstrap-tpls-0.11.2.js, angular-ui-sortable.js*/ \n'
      },
      build: {
        src: 'vendor/allVendorsConcatenated.js',
        dest: 'vendor.min.js'
      }
    }
    
  });

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-concat');

  // Default task(s).
  grunt.registerTask('vendor', ['concat', 'uglify']);

};