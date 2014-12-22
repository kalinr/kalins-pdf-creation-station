module.exports = function(grunt) {
  var pkg = grunt.file.readJSON('package.json'),
    svnTrunk = pkg.svnRepository + 'trunk/', //the trunk folder in our svn repository
    aFolders = ['help', 'tcpdf'], //list of folders we need copied
    aFiles = ['kalins_pdf_admin_page.php', 'kalins_pdf_create.php', 'kalins_pdf_styles.css', 'kalins_pdf_tool_page.php', 'kalins-pdf-creation-station.php', 
      'KalinsUIService.js', 'readme.txt', 'vendor.min.js'], //list of files actually used in the plugin
    aWatchList = aFiles.concat(['help/*', 'tcpdf/**/*']);//take our list of files and concat it with our list of folders with wildcards indicating all child files or folders and files.
      
  grunt.initConfig({
    pkg: pkg,

    concat: {
      options: {
        separator: '',
      },
      dist: {
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

    //phplint runs but does nothing. Can't figure it out 
    phplint: {
      main: ['kalins-pdf-creation-station.php', 'kalins_pdf_admin_page.php', 'kalins_pdf_tool_page.php', 'kalins_pdf_create.php']
    },
    
    watch: {
	  scripts: {
	    files: aWatchList,
	    options: {
	      spawn: false,
	      livereload: true//haven't actually done anything with this yet so probably doesn't work
	    }
	  }
	}
  });

  //function used for the 'release' task
  var releaseFunc = function (){
    var i = 0;

    verifyFiles();
    verifyVersion();

    //TODO: make a list of unused folders and unused files, then loop through all used and unused files and folders
    //and throw an error if there are any new or missing files or folders

    //TODO: make sure that we have added version # update info to both places in the readme.txt and updated it in 
    //kalins-pdf-creation-station.php, bower.json and package.json

    //loop into all our folders that we need moving
    for(i; i<aFolders.length; i++){
      grunt.file.recurse(aFolders[i], function(abspath, rootdir, subdir, filename){
        grunt.file.copy(abspath, svnTrunk + abspath);
      });
    }

    //copy over all our main files
    for(i = 0; i<aFiles.length; i++){
      grunt.file.copy(aFiles[i], svnTrunk + aFiles[i]);
    }

    //Finally, copy over the wordpress.org assets to the root of the svn repository, one level up from the trunk directory
    grunt.file.recurse('assets', function(abspath, rootdir, subdir, filename){
      grunt.file.copy(abspath, pkg.svnRepository + abspath);
    });
  }
  
  var verifyFiles = function (){
    grunt.log.writeln(pkg.svnRepository);
    
    grunt.log.oklns("Good to go: no top-level files or folders added or missing.");
  }
  
  var verifyVersion = function (){
    
    grunt.log.oklns("Good to go: version number has been properly updated in all the right places.");  
  }
  
  //listen for our any changed files so we can move them to the proper location in our dev wordpress installation
  grunt.event.on('watch', function(action, filepath, target) {
    var sDestination = pkg.devWordPressPath + "/wp-content/plugins/kalins-pdf-creation-station/" + filepath;
    grunt.log.writeln('Copying: ' + filepath + ' to ' + sDestination);
    grunt.file.copy(filepath, sDestination);
  });

  //load all our external modules (from node_modules dir) 
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-jslint');
  grunt.loadNpmTasks('grunt-phplint');
  grunt.loadNpmTasks('grunt-contrib-watch');

  //This is not intended to be run from command line
  //TODO: change this into a function that is called from vendor task so it's clear it's not intended for direct command line use
  grunt.registerTask('cleanup', 'delete our temp vendor file', function() {
    grunt.file.delete('dev/vendor.js');
  });

  //These are the tasks intended to run from command line
  grunt.registerTask('vendor', 'vendor tasks -- compile and minify the vendor js', ['concat', 'uglify', 'cleanup']);
  grunt.registerTask('lint', 'validate our JS and PHP', ['jslint', 'phplint:main']);
  grunt.registerTask('release', 'Move all relevant files into the proper location in the svn repository that you entered into the package.json file.', releaseFunc);
  grunt.registerTask('devwatch', 'Move any relevant changed files into proper locaton in our development WordPress installation as defined in package.json.', ['watch'])
};