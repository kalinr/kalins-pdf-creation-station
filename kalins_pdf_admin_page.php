<?php

	if ( !function_exists( 'add_action' ) ) {
		echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
		exit;
	}
	
	kalinsPDF_createPDFDir();
	
	$save_nonce = wp_create_nonce( 'kalins_pdf_admin_save' );
	$reset_nonce = wp_create_nonce( 'kalins_pdf_admin_reset' );
	$create_nonce = wp_create_nonce( 'kalins_pdf_create_all' );
	
	$adminOptions = kalins_pdf_get_admin_options();
?>


<script type='text/javascript'>

var app = angular.module('kalinsPDFAdminPage', ['ui.bootstrap']);

//TODO: turn this into a module in separate file so we don't repeat this code on the settings page
app.controller("UIController",["$scope", function($scope) {
	$scope.groupOpen = [true, true, true, true, true, true];
	$scope.bAllOpen = true;//state for close/open all button
	
	$scope.sToggleAllTrue = "Close All";
	$scope.sToggleAllFalse = "Open All";
	$scope.sToggleAll = $scope.sToggleAllTrue;//model string to show on close/open all button
	
	$scope.$watch('groupOpen', function(){
		var nStateCount = 0;
	
		//loop to see if we have opened or closed more than half the divs since the last time we clicked open/close all
		for(var i = 0; i < $scope.groupOpen.length; i++ ){
			if($scope.groupOpen[i] != $scope.bAllOpen){
				nStateCount++;
			}
		}
	
		//if we have opened/closed more than half, set the open/close all button text appropriately
		if(nStateCount > 3){
			$scope.bAllOpen = !$scope.bAllOpen;
			$scope.setToggleAllText();
		}
	}, true); 

	//open or close all main divs
	$scope.toggleAll = function(){		
		$scope.bAllOpen = !$scope.bAllOpen;
		for(var i = 0; i < $scope.groupOpen.length; i++ ){
			$scope.groupOpen[i] = $scope.bAllOpen;
		}
		$scope.setToggleAllText();
	}

	//set the text on the open/close all button
	$scope.setToggleAllText = function(){
		if($scope.bAllOpen){
			$scope.sToggleAll = $scope.sToggleAllTrue;
		}else{
			$scope.sToggleAll = $scope.sToggleAllFalse;
		}
	}	
}]);


app.controller("InputController",["$scope", "$http", function($scope, $http) {
	var self = this;

	var saveNonce = '<?php echo $save_nonce; //pass a different nonce security string for each possible ajax action?>';
	var resetNonce = '<?php echo $reset_nonce; ?>';
	var createAllNonce = '<?php echo $create_nonce; ?>';
		
	self.oOptions = <?php echo json_encode($adminOptions); ?>;

	//the text that shows under the create/reset/create all buttons indicating save status
	self.sCreateStatus = "";

	self.saveData = function(){
		//copy our data into new object
		var data = JSON.parse( JSON.stringify( self.oOptions ) );
		data.action = 'kalins_pdf_admin_save';//tell wordpress what to call
		data._ajax_nonce = saveNonce;//authorize it

		$http({method:"POST", url:ajaxurl, params: data}).
		  success(function(data, status, headers, config) {				
				if(data === "success"){
					self.sCreateStatus = "Settings saved successfully.";
				}else{
					self.sCreateStatus = data;
				}
		  }).
		  error(function(data, status, headers, config) {
		    self.sCreateStatus = "An error occurred: " + data;
		  });
	}

	self.resetToDefaults = function(){
		if(confirm("Are you sure you want to reset all of your field values? You will lose all the information you have entered and your cache of PDF files will be cleared.")){
			var data = { action: 'kalins_pdf_reset_admin_defaults', _ajax_nonce : resetNonce};

			$http({method:"POST", url:ajaxurl, params: data}).
			  success(function(data, status, headers, config) {
				  self.oOptions = data;
				  self.sCreateStatus = "Defaults reset successfully.";
			  }).
			  error(function(data, status, headers, config) {
			    self.sCreateStatus = "An error occurred: " + data;
			  });
		}
	}

	self.createAll = function(){
		var creationInProcess = false;
		
		var data = { action: 'kalins_pdf_create_all',
			_ajax_nonce : createAllNonce
		}
		
		if(!creationInProcess){
			self.sCreateStatus = "Creating PDF files for all pages and posts.";
		}

		$http({method:"POST", url:ajaxurl, params: data}).
		  success(function(data, status, headers, config) {
				if(data.status == "success"){
					if(data.existCount >= data.totalCount){
						self.sCreateStatus = data.totalCount  +  " PDF files successfully cached.";
						creationInProcess = false;
					}else{
						self.sCreateStatus = data.existCount + " out of " + data.totalCount  +  " PDF files cached. Now building the next " +  data.createCount + ".";
						creationInProcess = true;
						self.createAll();
					}
				}
		  }).
		  error(function(data, status, headers, config) {
		    self.sCreateStatus = "An error occurred: " + data;
		  });
	}
}]);
</script>

<div ng-app="kalinsPDFAdminPage" ng-controller="UIController as UICtrl" class="kContainer">
	<div ng-controller="InputController as InputCtrl">
	
		<h2>PDF Creation Station</h2>
		<h3>by Kalin Ringkvist - <a href="http://kalinbooks.com/">kalinbooks.com</a></h3>
		<p>Settings for creating PDF files on individual pages and posts. For more information, click the help button to the right.</p>
		
		<div class="form-group text-right">
			<button class="btn btn-info" ng-click="toggleAll();">{{sToggleAll}}</button>
		</div>
		
		<accordion close-others="false">
	    <accordion-group is-open="groupOpen[0]">
		    <accordion-heading>
		      <div><strong>Insert HTML before page or post</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': groupOpen[0], 'glyphicon-chevron-right': !groupOpen[0]}"></i></div>
	      </accordion-heading>
		    <b>HTML to insert before page:</b><br />
		    <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.beforePage"></textarea>
		    <b>HTML to insert before post:</b><br />
		    <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.beforePost"></textarea>
			</accordion-group>
			  			  
	    <accordion-group is-open="groupOpen[1]">
		    <accordion-heading>
		      <div><strong>Insert HTML after page or post</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': groupOpen[1], 'glyphicon-chevron-right': !groupOpen[1]}"></i></div>
	      </accordion-heading>
        <b>HTML to insert after page:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.afterPage"></textarea>
        <b>HTML to insert after post:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.afterPost"></textarea>
			</accordion-group>
			  
			<accordion-group is-open="groupOpen[2]">
		    <accordion-heading>
		      <div><strong>Insert HTML for title and final pages</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': groupOpen[2], 'glyphicon-chevron-right': !groupOpen[2]}"></i></div>
	      </accordion-heading>
        <b>HTML to insert for title page:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.titlePage"></textarea>
        <b>HTML to insert for final page:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.finalPage"></textarea>
			</accordion-group>
			
			<accordion-group is-open="groupOpen[3]">
		    <accordion-heading>
		      <div><strong>Options</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': groupOpen[3], 'glyphicon-chevron-right': !groupOpen[3]}"></i></div>
	      </accordion-heading>
	      <form class="form-horizontal" role="form">
		      <div class="form-group">
			      <label for="txtHeaderTitle" class="control-label col-xs-2">Header title:</label>
			      <div class="col-xs-10">
			      	<input id="txtHeaderTitle" type='text' class="form-control" ng-model="InputCtrl.oOptions.headerTitle"></input>
			      </div>
			    </div>
			    <div class="form-group">
			      <label for="txtHeaderSub" class="control-label col-xs-2">Header sub title:</label>
			      <div class="col-xs-10">
			      	<input id="txtHeaderSub" type='text' class="form-control" ng-model="InputCtrl.oOptions.headerSub"></input>
			      </div>
			    </div>
			    <div class="form-group">
			    	<label for="numFontSize" class="control-label col-xs-2">Content font size:</label>
			    	<div class="col-xs-4 col-sm-2">
			    		<input type="number" ng-model="InputCtrl.oOptions.fontSize" id="numFontSize" class="form-control" /> 
			    	</div>
			    </div>
			    <div class="form-group col-md-6 col-xs-12" >
				    <div class="checkbox col-md-offset-2">
				      <label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.includeImages"></input> Include Images</label>
				    </div>
				    <div class="checkbox col-md-offset-2"> 	  
				      <label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.runShortcodes"></input> Run other plugin shortcodes,</label>
				    </div>
				    <div class="checkbox col-md-offset-2">
				      <label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.runFilters"></input> and content filters</label>
				    </div>
			    </div>
			 		<div class="form-group col-md-6 col-xs-12" >
			      <b>Convert videos to links:</b>
			      <div class="checkbox">
			      	<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.convertYoutube"></input> YouTube,</label>
			      </div>
				    <div class="checkbox"> 
			       	<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.convertVimeo"></input> Vimeo,</label>
			      </div>
				    <div class="checkbox"> 
			       	<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.convertTed"></input> Ted Talks</label>
			      </div>
			    </div>
			    
			    <div class="form-group">
			      <label for="txtLink" class="control-label col-xs-2">Link text:</label>
			      <div class="col-xs-10">
			      	<input type="text" id="txtLink" class="form-control" ng-model="InputCtrl.oOptions.linkText"></input>
			     	</div>
			    </div>
			    
			    <div class="form-group">
			      <label for="txtBeforeLink" class="control-label col-xs-2">Before link:</label>
			      <div class="col-xs-10">
			      	<input type="text" id="txtBeforeLink" class="form-control" ng-model="InputCtrl.oOptions.beforeLink"></input>
			      </div>
			    </div>
			    <div class="form-group">
			      <label for="txtAfterLink" class="control-label col-xs-2">After link:</label>
			      <div class="col-xs-10">
			      	<input type="text" id="txtAfterLink" class="form-control" ng-model="InputCtrl.oOptions.afterLink"></input>
			      </div>
			    </div>
					
			    <div class="form-group">
						<p class="col-md-offset-1"><b>Default Link Placement</b> (can be overwritten in page/post edit page):</p>
					  <div class="btn-group col-md-offset-1" data-toggle="buttons" >
					    <label class="btn btn-success" ng-class="{ 'active': InputCtrl.oOptions.showLink == 'top'}"><input type="radio" value="top" ng-model="InputCtrl.oOptions.showLink" /> Link at top of page </label>
					    <label class="btn btn-success" ng-class="{ 'active': InputCtrl.oOptions.showLink == 'bottom'}"><input type="radio" value="bottom" ng-model="InputCtrl.oOptions.showLink" /> Link at bottom of page </label>
					    <label class="btn btn-success" ng-class="{ 'active': InputCtrl.oOptions.showLink == 'none'}"><input type="radio" value="none" ng-model="InputCtrl.oOptions.showLink" /> Do not generate PDF </label>
					  </div>
					</div>
					
			    <div class="form-group">
			    	<label for="numWordCount" class="control-label col-xs-2">Minimum post word count:</label>
			    	<div class="col-xs-4 col-sm-2">
			    		<input type="number" ng-model="InputCtrl.oOptions.wordCount" id="numWordCount" class="form-control" /> 
			    	</div>
			    </div>

		      <div class="form-group col-xs-12" >
		      	<div class="checkbox col-md-offset-1">
		      		<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.filenameByTitle"></input> Use post slug for PDF filename instead of ID</label>
		      	</div>
		      	<div class="checkbox col-md-offset-1">
		      		<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.showOnMulti"></input> Show on home, category, tag and search pages (does not work if you use excerpts on any of these pages)</label>
		        </div>
		        <div class="checkbox col-md-offset-1">
		      		<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.autoGenerate"></input> Automatically generate PDFs on publish and update</label>
		      	</div>
					</div>

					<div class="form-group text-center">
		        <button ng-click="InputCtrl.saveData()" class="btn btn-success">Save Settings</button>
		        <button ng-click="InputCtrl.resetToDefaults()" class="btn btn-warning">Reset Defaults</button>
		        <button ng-click="InputCtrl.createAll()" class="btn btn-success">Create All</button>
		      </div>
		      <p align="center"><span id="createStatus">{{InputCtrl.sCreateStatus}}</span></p>
			  </form>
			</accordion-group>
			
			<accordion-group is-open="groupOpen[4]">
		    <accordion-heading>
		      <div><strong>Shortcodes</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': groupOpen[4], 'glyphicon-chevron-right': !groupOpen[4]}"></i></div>
	      </accordion-heading>   
		  	<b>shortcodes:</b> Use these codes anywhere in the above form to insert blog or page information.
		  	<p><ul>
		      <li><b>[current_time format="m-d-Y"]</b> -  PDF creation date/time <b>*</b></li>
		      <li><b>[blog_name]</b> -  the name of the blog</li>
		      <li><b>[blog_description]</b> - description of the blog</li>
		      <li><b>[blog_url]</b> - blog base url</li>
		      <li><b>[ID]</b> - the ID number of the page/post</li>
		      <li><b>[post_author type="display_name"]</b> - post author information. Possible types: ID, user_login, user_pass, user_nicename, user_email, user_url, display_name, user_firstname, user_lastname, nickname, description, primary_blog</li>
		      <li><b>[post_permalink]</b> - the page permalink</li>
		      <li><b>[post_date format="m-d-Y"]</b> - date page/post was created <b>*</b></li>
		      <li><b>[post_date_gmt format="d-m-Y"]</b> - date page/post was created in gmt time <b>*</b></li>
		      <li><b>[post_title]</b> - page/post title</li>
		      <li><b>[post_excerpt length="250"]</b> - page/post excerpt (note the optional character 'length' parameter)</li>
		      <li><b>[post_name]</b> - page/post slug name</li>
		      <li><b>[post_modified format="m-d-Y"]</b> - date page/post was last modified <b>*</b></li>
		      <li><b>[post_modified_gmt format="m-d-Y"]</b> - date page/post was last modified in gmt time <b>*</b></li>
		      <li><b>[guid]</b> - url of the page/post</li>
		      <li><b>[comment_count]</b> - number of comments posted for this post/page</li>
		      <li><b>[post_meta name="custom_field_name"]</b> - page/post custom field value. Correct 'name' parameter required</li>
		      <li><b>[post_tags delimeter=", " links="true"]</b> - post tags list. Optional 'delimiter' parameter sets separator text. Use optional 'links' parameter to turn off links to tag pages</li>
		      <li><b>[post_categories delimeter=", " links="true"]</b> - post categories list. Parameters work like tag shortcode.</li>
		      <li><b>[post_parent link="true"]</b> - post parent. Use optional 'link' parameter to turn off link</li>
		      <li><b>[post_comments before="" after=""]</b> - post comments. Parameters represent text/HTML that will be inserted before and after comment list but will not be displayed if there are no comments. PHP coders: <a href="http://kalinbooks.com/2011/customize-comments-pdf-creation-station">learn how to customize comment display.</a></li>
		      <li><b>[post_thumb size="full" extract="none"]</b> - URL to the page/post's featured image (requires theme support). Possible size paramaters: "thumbnail", "medium", "large" or "full". Possible extract prameters: "on" or "force". Setting extract to "on" will cause the shortcode to attempt to pull the first image from within the post if it cannot find a featured image. Using "force" will cause it to ignore the featured image altogether. Extracted images always return at the same size they appear in the post.</li>
		    </ul></p>
		    <p><b>*</b> Time shortcodes have an optional format parameter. Format your dates using these possible tokens: m=month, M=text month, F=full text month, d=day, D=short text Day Y=4 digit year, y=2 digit year, H=hour, i=minute, s=seconds. More tokens listed here: <a href="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">http://codex.wordpress.org/Formatting_Date_and_Time.</a> </p>
		        
		    <p><b>Note: these shortcodes only work on this page.</b></p>
		    
		    <hr/>
	
		    <p><b>The following tags are supported wherever HTML is allowed (according to TCPDF documentation):</b><br /> a, b, blockquote, br, dd, del, div, dl, dt, em, font, h1, h2, h3, h4, h5, h6, hr, i, img, li, ol, p, pre, small, span, strong, sub, sup, table, tcpdf, td, th, thead, tr, tt, u, ul. Also supports some XHTML, CSS, JavaScript and forms.</p>
		    <p>Please use double quotes (") in HTML attributes such as font size or href, due to a bug with single quotes.</p> 
			</accordion-group>
			
			<accordion-group is-open="groupOpen[5]">
		    <accordion-heading>
		      <div><strong>About</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': groupOpen[5], 'glyphicon-chevron-right': !groupOpen[5]}"></i></div>
	      </accordion-heading> 
		  	<p>Thank you for using PDF Creation Station. To report bugs, request help or suggest features, visit <a href="http://kalinbooks.com/pdf-creation-station/" target="_blank">KalinBooks.com/pdf-creation-station</a>. If you find this plugin useful, please consider <A href="http://wordpress.org/extend/plugins/kalins-pdf-creation-station/">rating this plugin on WordPress.org</A> or making a PayPal donation:</p>
		       
				<p>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="C6KPVS6HQRZJS">
						<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="Donate to Kalin Ringkvist's WordPress plugin development.">
						<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
				</p>
				<br/>
		    <p><input type='checkbox' ng-model="InputCtrl.oOptions.doCleanup"></input> Upon plugin deactivation clean up all database entries</p>
		    <p>You may also like <a href="http://kalinbooks.com/easy-edit-links-wordpress-plugin/" target="_blank">Kalin's Easy Edit Links</a> - <br /> Adds a box to your page/post edit screen with links and edit buttons for all pages, posts, tags, categories, and links for convenient edit-switching and internal linking.</p>       
		    <p>Or <a href="http://kalinbooks.com/post-list-wordpress-plugin/" target="_blank">Kalin's Post List</a> - <br /> Use a shortcode in your posts to insert dynamic, highly customizable lists of posts, pages, images, or attachments based on categories and tags. Works for table-of-contents pages or as a related posts plugin.</p>
			</accordion-group>
		</accordion>
	</div>
</div>
</html>