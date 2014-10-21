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

jQuery(document).ready(function($){
	
	var saveNonce = '<?php echo $save_nonce; //pass a different nonce security string for each possible ajax action?>'
	var resetNonce = '<?php echo $reset_nonce; ?>'
	var createAllNonce = '<?php echo $create_nonce; ?>'
	
	$('#btnCreateAll').click(function(){
		callCreateAll();
	});
	
	var creationInProcess = false;
	
	function callCreateAll(){
		var data = { action: 'kalins_pdf_create_all',
			_ajax_nonce : createAllNonce
		}
		
		if(!creationInProcess){
			$('#createStatus').html("Creating PDF files for all pages and posts.");
		}

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			var startPosition = response.indexOf("{");
			var responseObjString = response.substr(startPosition, response.lastIndexOf("}") - startPosition + 1);
			
			var newFileData = JSON.parse(responseObjString);
			if(newFileData.status == "success"){
				
				if(newFileData.existCount >= newFileData.totalCount){
					$('#createStatus').html(newFileData.totalCount  +  " PDF files successfully cached.");
					creationInProcess = false;
				}else{
					$('#createStatus').html(newFileData.existCount + " out of " + newFileData.totalCount  +  " PDF files cached. Now building the next " +  newFileData.createCount + ".");
					creationInProcess = true;
					callCreateAll();
				}
			}else{
				$('#createStatus').html(response);
			}
		});
	}
	
});


var app = angular.module('kalinsPDFAdminPage', []);

app.controller("UIController",["$scope", function($scope) {
	var self = this;
	self.aCollapsed = [false, false, false, false, false, false];//list of states for the main divs
	self.bAllCollapsed = false;//state for close/open all button
	self.sToggleAllTrue = "Open All";
	self.sToggleAllFalse = "Close All";
	self.sToggleAll = self.sToggleAllFalse;//model string to show on close/open all button

	//toggle a single div open/closed
	self.toggleCollapsed = function(index){
		self.aCollapsed[index] = !self.aCollapsed[index];
		var nStateCount = 0;

		//loop to see if we have opened or closed more than half the divs since the last time we clicked open/close all
		for(var i = 0; i < self.aCollapsed.length; i++ ){
			if(self.aCollapsed[i] != self.bAllCollapsed){
				nStateCount = nStateCount + 1;
			}
		}

		//if we have opened/closed more than half, set the open/close all button text appropriately
		if(nStateCount>3){
			self.bAllCollapsed = !self.bAllCollapsed;
			self.setToggleAllText();
		}
	}

	//open or close all main divs
	self.toggleAll = function(){
		self.bAllCollapsed = !self.bAllCollapsed;
		for(var i = 0; i < self.aCollapsed.length; i++ ){
			self.aCollapsed[i] = self.bAllCollapsed;
		}
		self.setToggleAllText();
	}

	//set the text on the open/close all button
	self.setToggleAllText = function(){
		if(self.bAllCollapsed){
			self.sToggleAll = self.sToggleAllTrue;
		}else{
			self.sToggleAll = self.sToggleAllFalse;
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

	console.log(self.oOptions);

	self.saveData = function(){
		//copy our data into new object
		var data = JSON.parse( JSON.stringify( self.oOptions ) );
		data.action = 'kalins_pdf_admin_save';//tell wordpress what to call
		data._ajax_nonce = saveNonce;//authorize it

		console.log(data);

		$http({method:"POST", url:ajaxurl, params: data}).
		  success(function(data, status, headers, config) {				
				if(data.indexOf("success") > -1){
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
				  self.oOptions = JSON.parse(data.substr(0, data.lastIndexOf("}") + 1));
				  self.sCreateStatus = "Defaults reset successfully.";
			  }).
			  error(function(data, status, headers, config) {
			    self.sCreateStatus = "An error occurred: " + data;
			  });
		}
	}
	

}]);


</script>
<div ng-app="kalinsPDFAdminPage" ng-controller="UIController as UICtrl">
	<div ng-controller="InputController as InputCtrl">
	
		<h2>PDF Creation Station</h2>
		<h3>by Kalin Ringkvist - <a href="http://kalinbooks.com/">kalinbooks.com</a></h3>
		<p>Settings for creating PDF files on individual pages and posts. For more information, click the help button to the right.</p>
		
		<div class="collapse" ng-click="UICtrl.toggleAll()"><b>{{UICtrl.sToggleAll}}</b></div>
		
		<div class="collapse" ng-click="UICtrl.toggleCollapsed(0)"><b>Insert HTML before page or post</b></div>
	  <div class="txtfieldHolder" ng-hide="UICtrl.aCollapsed[0]">
	    <div class="textAreaDiv">
	      <b>HTML to insert before page:</b><br />
	      <textarea class="txtArea" name='txtBeforePage' id='txtBeforePage' rows='8' ng-model="InputCtrl.oOptions.beforePage"></textarea>
	    </div>
	    <div class="textAreaDiv">
	      <b>HTML to insert before post:</b><br />
	      <textarea class="txtArea" name='txtBeforePost' id='txtBeforePost' rows='8' ng-model="InputCtrl.oOptions.beforePost"></textarea>
	    </div>
	  </div>
	  
	  <div class='collapse' ng-click="UICtrl.toggleCollapsed(1)"><b>Insert HTML after page or post</b></div>
	  <div class="txtfieldHolder" ng-hide="UICtrl.aCollapsed[1]">
	      <div class="textAreaDiv">
	          <b>HTML to insert after page:</b><br />
	          <textarea class="txtArea" name='txtAfterPage' id='txtAfterPage' rows='8' ng-model="InputCtrl.oOptions.afterPage"></textarea>
	      </div>
	      <div class="textAreaDiv">
	          <b>HTML to insert after post:</b><br />
	          <textarea class="txtArea" name='txtAfterPost' id='txtAfterPost' rows='8' ng-model="InputCtrl.oOptions.afterPost"></textarea>
	      </div>
	  </div>
	  
	  <div class='collapse' ng-click="UICtrl.toggleCollapsed(2)"><b>Insert HTML for title and final pages</b></div>
	  <div class="txtfieldHolder" ng-hide="UICtrl.aCollapsed[2]">
	      <div class="textAreaDiv">
	          <b>HTML to insert for title page:</b><br />
	          <textarea class="txtArea" name='txtTitlePage' id='txtTitlePage' rows='8' ng-model="InputCtrl.oOptions.titlePage"></textarea>
	      </div>
	      <div class="textAreaDiv">
	          <b>HTML to insert for final page:</b><br />
	          <textarea class="txtArea" name='txtFinalPage' id='txtFinalPage' rows='8' ng-model="InputCtrl.oOptions.finalPage"></textarea>
	      </div>
	  </div>
	  
	  <div class='collapse' ng-click="UICtrl.toggleCollapsed(3)"><b>Options</b></div>
	  <div class="generalHolder" ng-hide="UICtrl.aCollapsed[3]">
	      <p>Header title: <input type='text' name='txtHeaderTitle' id='txtHeaderTitle' class='txtHeader' ng-model="InputCtrl.oOptions.headerTitle"></input></p>
	      <p>Header sub title: <input type='text' name='txtHeaderSub' id='txtHeaderSub' class='txtHeader' ng-model="InputCtrl.oOptions.headerSub"></input></p>
	      <br/>
	      <p>Link text: <input type="text" id='txtLinkText' class='txtHeader' ng-model="InputCtrl.oOptions.linkText"></input></p>
	      <p>Before Link: <input type="text" id='txtBeforeLink' class='txtHeader' ng-model="InputCtrl.oOptions.beforeLink"></input></p>
	      <p>After Link: <input type="text" id='txtAfterLink' class='txtHeader' ng-model="InputCtrl.oOptions.afterLink"></input></p>
	      <br/>
	      <p><input type='checkbox' id='chkIncludeImages' name='chkIncludeImages' ng-model="InputCtrl.oOptions.includeImages" ></input> Include Images &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type="text" id="txtFontSize" size="2" maxlength="3" ng-model="InputCtrl.oOptions.fontSize" /> Content font size &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type='checkbox' id='chkRunShortcodes' name='chkRunShortcodes' ng-model="InputCtrl.oOptions.runShortcodes"></input> Run other plugin shortcodes, &nbsp;<input type='checkbox' id='chkRunFilters' name='chkRunFilters' ng-model="InputCtrl.oOptions.runFilters"></input> and content filters</p>
	      <p>Convert videos to links: &nbsp;&nbsp;<input type='checkbox' id='chkConvertYoutube' name='chkConvertYoutube' ng-model="InputCtrl.oOptions.convertYoutube"></input> YouTube, &nbsp;<input type='checkbox' id='chkConvertVimeo' name='chkConvertVimeo' ng-model="InputCtrl.oOptions.convertVimeo"></input> Vimeo, &nbsp;<input type='checkbox' id='chkConvertTed' name='chkConvertTed' ng-model="InputCtrl.oOptions.convertTed"></input> Ted Talks</p>
	      <br/>
	      
	      <p>Default Link Placement (can be overwritten in page/post edit page):</p>
			
			  <p>
			    <input type="radio" name="kalinsPDFLink" value="top" id="opt_top" ng-model="InputCtrl.oOptions.showLink" /> Link at top of page<br />
			    <input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" ng-model="InputCtrl.oOptions.showLink" /> Link at bottom of page<br />
			    <input type="radio" name="kalinsPDFLink" value="none" id="opt_none" ng-model="InputCtrl.oOptions.showLink" /> Do not generate PDF
			  </p>
			
	      <p><input type="text" id="txtWordCount" size="3" maxlength="5" ng-model="InputCtrl.oOptions.wordCount" /> Minimum post word count
	      </p>
	      <br/>
	      <p><input type='checkbox' id='chkFilenameByTitle' name='chkFilenameByTitle' ng-model="InputCtrl.oOptions.filenameByTitle"></input> Use post slug for PDF filename instead of ID</p>
	      
	      <p><input type='checkbox' id='chkShowOnMulti' name='chkShowOnMulti' ng-model="InputCtrl.oOptions.showOnMulti"></input> Show on home, category, tag and search pages (does not work if you use excerpts on any of these pages)</p>
	        
	      <p><input type='checkbox' id='chkAutoGenerate' name='chkAutoGenerate' ng-model="InputCtrl.oOptions.autoGenerate"></input> Automatically generate PDFs on publish and update</p><br/>
	        
	      <p><!--&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type='checkbox' id='chkIncludeTables' name='chkIncludeTables' if($adminOptions["includeTables"] == 'true'){echo "checked='yes' ";} ></input> Include Tables --></p>

				<p align="center"><br />
	        <button id="btnSave" ng-click="InputCtrl.saveData()">Save Settings</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button type='button' id='btnReset' ng-click="InputCtrl.resetToDefaults()">Reset Defaults</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button type='button' id='btnCreateAll'>Create All</button></p>
	        <p align="center"><span id="createStatus">{{InputCtrl.sCreateStatus}}</span></p>
	  </div>
	    
	    
	  <div class='collapse' ng-click="UICtrl.toggleCollapsed(4)"><b>Shortcodes</b></div>
	  <div class="generalHolder" ng-hide="UICtrl.aCollapsed[4]">
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
	  </div>
	    
	  <div class='collapse' ng-click="UICtrl.toggleCollapsed(5)"><b>About</b></div>
	  <div class="generalHolder" ng-hide="UICtrl.aCollapsed[5]">  
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
	      
	    <p><input type='checkbox' id='chkDoCleanup' name='chkDoCleanup' ng-model="InputCtrl.oOptions.doCleanup"></input> Upon plugin deactivation clean up all database entries</p>
	
	    <p>You may also like <a href="http://kalinbooks.com/easy-edit-links-wordpress-plugin/" target="_blank">Kalin's Easy Edit Links</a> - <br /> Adds a box to your page/post edit screen with links and edit buttons for all pages, posts, tags, categories, and links for convenient edit-switching and internal linking.</p>
	         
	    <p>Or <a href="http://kalinbooks.com/post-list-wordpress-plugin/" target="_blank">Kalin's Post List</a> - <br /> Use a shortcode in your posts to insert dynamic, highly customizable lists of posts, pages, images, or attachments based on categories and tags. Works for table-of-contents pages or as a related posts plugin.</p>
    
	  </div>
	</div>
</div>
</html>