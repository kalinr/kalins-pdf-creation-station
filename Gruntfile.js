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
    },
    
    jslint: {
      //TODO: pull js code out of tool and admin pages into their own files so we can lint them
      src: ['KalinsUIService.js']    // 'src/**/*.js', 'test/**/*.js']
    },
    
    //phplint runs but does nothing. Can't figure out 
    phplint: {
      main: ['kalins-pdf-creation-station.php', 'kalins_pdf_admin_page.php', 'kalins_pdf_tool_page.php', 'kalins_pdf_create.php']
    }
    
  });

  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.loadNpmTasks('grunt-jslint');
  grunt.loadNpmTasks('grunt-phplint');

  grunt.registerTask('cleanup', 'delete our temp vendor file', function() {
	grunt.file.delete('dev/vendor.js');
  });

  grunt.registerTask('vendor', 'vendor tasks -- compile and minify the vendor js', ['concat', 'uglify', 'cleanup']);

  grunt.registerTask('lint', 'validate our JS and PHP', ['jslint', 'phplint:main']);

};