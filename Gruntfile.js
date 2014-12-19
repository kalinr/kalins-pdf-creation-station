module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    
    concat: {
      options: {
        separator: '',
      },
      dist: {
        //src: ['vendor/angular.js', 'vendor/ui-bootstrap-tpls-0.11.2.js', 'vendor/angular-ui-sortable.js', 'vendor/ng-table.js'],
        src: ['bower_components/angular/angular.js', 'bower_components/angular-ui-sortable/sortable.js',  'bower_components/ng-table/ng-table.js', 'bower_components/angular-bootstrap/ui-bootstrap-tpls.js'],
        dest: 'dev/vendor.js'
      },
    },
    
    uglify: {
      options: {
        banner: '/*! \nKalin\'s PDF Creation Station <%= grunt.template.today("yyyy-mm-dd") %> \nThis JavaScript file contains: angular.js, ng-table.js, ui-bootstrap-tpls-0.11.2.js, angular-ui-sortable.js*/ \n'
      },
      build: {
        src: 'dev/vendor.js',
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