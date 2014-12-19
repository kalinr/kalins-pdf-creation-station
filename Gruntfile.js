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
        banner: '/*! \nKalin\'s PDF Creation Station <%= grunt.template.today("yyyy-mm-dd") %> \nThis JavaScript file contains: angular.js, ng-table.js, ui-bootstrap-tpls.js and angular-ui-sortable.js*/ \n'
      },
      build: {
        src: 'dev/vendor.js',
        dest: 'vendor.min.js'
      }
    }
    
  });

  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  
  grunt.registerTask('cleanup', 'delete our temp vendor file', function() {
	grunt.file.delete('dev/vendor.js');
  });

  //vendor tasks -- compile and minify the vendor js
  grunt.registerTask('vendor', ['concat', 'uglify', 'cleanup']);

};