<?php
	
	if ( !function_exists( 'add_action' ) ) {
		echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
		exit;
	}
		
	kalinsPDF_createPDFDir();//make sure our PDF dir exists
	
	$create_nonce = wp_create_nonce( 'kalins_pdf_tool_create' );
	$save_nonce = wp_create_nonce( 'kalins_pdf_tool_save' );
	$delete_template_nonce = wp_create_nonce( 'kalins_pdf_tool_template_delete' );
	$delete_nonce = wp_create_nonce( 'kalins_pdf_tool_delete' );
	$reset_nonce = wp_create_nonce( 'kalins_pdf_tool_reset' );
	
	$adminOptions = kalins_pdf_get_options( KALINS_PDF_TOOL_OPTIONS_NAME );
	
	$allPostList = get_posts('numberposts=-1&post_type=any&post_status=any');
		
	$customList = array();
		
	$l = count($allPostList);
	for($i=0; $i<$l; $i++){
		if($allPostList[$i]->post_type === "nav_menu_item" || $allPostList[$i]->post_type === "attachment" || $allPostList[$i]->post_type === "revision"){
			continue;
		}
		
		$newItem = new stdClass();
		$newItem->ID = $allPostList[$i]->ID;
		$newItem->title = $allPostList[$i]->post_title;
		$newItem->type = $allPostList[$i]->post_type;
		$newItem->date = substr($allPostList[$i]->post_date, 0, strpos($allPostList[$i]->post_date, " "));
		$newItem->status = $allPostList[$i]->post_status;
		
		array_push($customList, $newItem);		
	}
	
	$pdfList = array();
	$pdfDir = KALINS_PDF_DIR;
	
	if ($handle = opendir($pdfDir)) {
		while (false !== ($file = readdir($handle))) {
			//loop to find all relevant files (stripos is not case sensitive so it finds .PDF, .HTML, .TXT)
			if ($file != "." && $file != ".." && (stripos($file, ".pdf") > 0 || stripos($file, ".html") > 0 || stripos($file, ".txt") > 0  )) {
				$fileObj = new stdClass();
				$fileObj->fileName = $file;
				$fileObj->date = date("Y-m-d H:i:s", filemtime($pdfDir .$file));
				array_push($pdfList, $fileObj);//compile array of file information simply to pass to javascript				
			}
		}
		closedir($handle);
	}
	
	//get our list of document templates from the database
	$templateOptions = get_option( KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME );
	
	//get our help strings to populate the rollovers on the info icons
	$toolStrings = file_get_contents(WP_PLUGIN_DIR . '/kalins-pdf-creation-station/help/toolStrings.json');
	
	/*
	 require_once 'ProgressBar.class.php';
	 global $proBar;
 	$proBar = new ProgressBar();
	$proBar->setMessage('loading ...');
 	$proBar->setAutohide(true);
 	$proBar->setSleepOnFinish(1);
 	//$proBar->setForegroundColor('#ff0000');

 	$elements = 100; //total number of elements to process
	*/	
?>

<script type='text/javascript'>

var app = angular.module('kalinsPDFToolPage', ['ngTable', 'ui.sortable', 'ui.bootstrap', 'kalinsUI']);

app.controller("InputController",["$scope", "$http", "$filter", "ngTableParams", "kalinsToggles", "kalinsAlertManager", function($scope, $http, $filter, ngTableParams, kalinsToggles, kalinsAlertManager) {
	//TODO: angular's ui-sortable actually uses jQuery. replace it with what we find here to eliminate jQuery:
	//http://amnah.net/2014/02/18/how-to-set-up-sortable-in-angularjs-without-jquery/
	
	//build our toggle manager for the accordion's toggle all button
	$scope.kalinsToggles = new kalinsToggles([true, true, true, true, true, true, true, true, true, true], "Close All", "Open All", "kalinsToolPageAccordionToggles");

	//set up the alerts that show under the form buttons
	$scope.kalinsAlertManager = new kalinsAlertManager(4);

	$scope.oHelpStrings = <?php echo $toolStrings ?>;
	
	var self = this;
	var createNonce = '<?php echo $create_nonce; //pass a different nonce security string for each possible ajax action?>'
	var saveNonce = '<?php echo $save_nonce; ?>';
	var deleteTemplateNonce = '<?php echo $delete_template_nonce; ?>';
	var deleteNonce = '<?php echo $delete_nonce; ?>';
	var resetNonce = '<?php echo $reset_nonce; ?>';
	
	self.pdfUrl = "<?php echo KALINS_PDF_URL; ?>";
	self.pdfList = <?php echo json_encode($pdfList); ?>;
	self.postList = <?php echo json_encode($customList); ?>;
	self.templateList = <?php echo json_encode($templateOptions->aTemplates); ?>;
	self.oOptions = <?php echo json_encode($adminOptions); ?>;
	self.buildPostList = [];//the post list that will eventually be compiled into the pdf
	self.buildPostListByID = [];//associative array by postID to track which rows to highlight
	self.sNewTemplateName = "";
	
  $scope.postListTableParams = new ngTableParams({
    page: 1,            // show first page
    count: 10,          // count per page
    sorting: {
      title: 'asc'     // initial sorting
    }
  }, {
    total: self.postList.length, // length of postList
    getData: function($defer, params) {
      var filteredData = params.filter() ?
          $filter('filter')(self.postList, params.filter()) :
          self.postList;
      var orderedData = params.sorting() ?
          $filter('orderBy')(filteredData, params.orderBy()) :
          self.postList;

      params.total(orderedData.length); // set total for recalc pagination
      $defer.resolve(orderedData.slice((params.page() - 1) * params.count(), params.page() * params.count()));	      
    }
  });

  $scope.pdfListTableParams = new ngTableParams({
    page: 1,            // show first page
    count: 10,          // count per page
    sorting: {
      fileName: 'asc'     // initial sorting
    }
  }, {
    total: self.pdfList.length, // length of pdfList
    getData: function($defer, params) {
      var filteredData = params.filter() ?
          $filter('filter')(self.pdfList, params.filter()) :
          self.pdfList;
      var orderedData = params.sorting() ?
          $filter('orderBy')(filteredData, params.orderBy()) :
          self.pdfList;

      params.total(orderedData.length); // set total for recalc pagination
      $defer.resolve(orderedData.slice((params.page() - 1) * params.count(), params.page() * params.count()));
    }
  });

  $scope.templateListTableParams = new ngTableParams({
	    page: 1,            // show first page
	    count: 10,          // count per page
	    sorting: {
	      date: 'desc'     // initial sorting
	    }
	  }, {
	    total: self.templateList.length, // length of templateList
	    getData: function($defer, params) {
	      var filteredData = params.filter() ?
	          $filter('filter')(self.templateList, params.filter()) :
	          self.templateList;
	      var orderedData = params.sorting() ?
	          $filter('orderBy')(filteredData, params.orderBy()) :
	          self.templateList;

	      params.total(orderedData.length); // set total for recalc pagination
	      $defer.resolve(orderedData.slice((params.page() - 1) * params.count(), params.page() * params.count()));
	    }
	  });

	self.resetToDefaults = function(){		
		if(confirm("Are you sure you want to reset all of your field values? You will lose all the information you have entered into the form. (This will NOT delete or change your existing PDF documents.)")){
			var data = { action: 'kalins_pdf_tool_defaults', _ajax_nonce : resetNonce};

			$http({method:"POST", url:ajaxurl, params: data}).
			  success(function(data, status, headers, config) {
				  self.oOptions = data;
				  $scope.kalinsAlertManager.addAlert("Defaults reset successfully.", "success");
			  }).
			  error(function(data, status, headers, config) {
			    $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
			  });
		}
	}

	self.createDocument = function(){
		if(self.buildPostList.length === 0){
			$scope.kalinsAlertManager.addAlert("Error: You need to add at least one page or post.", "danger");
			return;
		}

		if(!self.oOptions.bCreatePDF && !self.oOptions.bCreateHTML && !self.oOptions.bCreateTXT){
			$scope.kalinsAlertManager.addAlert("Error: You need to select at least one filetype: .pdf, .html or .txt.", "danger");
			return;
		}

		var data = {};
		data.oOptions = JSON.stringify( self.oOptions );
		data.action = 'kalins_pdf_tool_create';//tell wordpress what to call
		data._ajax_nonce = createNonce;//authorize it
		data.pageIDs = "";
		
		//loop to compile a string of pa_ and po_ that tells php which pages/posts to compile and whether to treat them as a page or post
		for(var i = 0; i < self.buildPostList.length; i++){
			if(self.buildPostList[i].type === "page"){
				data.pageIDs = data.pageIDs + "pa_" + self.buildPostList[i].ID;
			}else{
				data.pageIDs = data.pageIDs + "po_" + self.buildPostList[i].ID;
			}
			if(i < self.buildPostList.length - 1){
				data.pageIDs += ",";
			}
		}
		$scope.kalinsAlertManager.addAlert("Building PDF file. Wait time will depend on the length of the document, image complexity and current server load. Refreshing the page or navigating away will cancel the build.", "success");
		
		$http({method:"POST", url:ajaxurl, params: data}).
		  success(function(data, status, headers, config) {
				if(data.status == "success"){

					var l = data.aFiles.length;
					//loop to add all our new files (up to three, .html, .pdf and .txt)
					for(var i = 0; i<l; i++){
						self.pdfList.push(data.aFiles[i]);
					}
					$scope.pdfListTableParams.reload();
					$scope.kalinsAlertManager.addAlert("File created successfully", "success");
				}else{
					$scope.kalinsAlertManager.addAlert("Error: " + data.status, "danger");
				}
		  }).
		  error(function(data, status, headers, config) {
		    $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
		  });
	}

	self.saveTemplate = function(){
		var l = self.templateList.length,
		data = {},
		nReplaceIndex = -1;

		if(self.sNewTemplateName === ""){
			$scope.kalinsAlertManager.addAlert("Error: You need to enter a name for your template.", "danger");
			return;
		}
		
		for(var i= 0; i<l; i++){
			if( self.templateList[i].templateName === self.sNewTemplateName ){
				nReplaceIndex = i;
				break;
			}
		}

		if(nReplaceIndex >= 0){
			if(!confirm("You already have a template named " + self.sNewTemplateName + ". Would you like to overwrite it?" )){
				return;
			}
		}
		
		data.action = 'kalins_pdf_tool_save';//tell wordpress what to call
		data._ajax_nonce = saveNonce;//authorize it
		data.pageIDs = "";
		//copy our main object so we can mess with it
		data.oOptions = JSON.parse(JSON.stringify( self.oOptions ));
		data.oOptions.buildPostList = self.buildPostList;
		data.oOptions.templateName = self.sNewTemplateName;
		data.oOptions = JSON.stringify( data.oOptions );
				
		$http({method:"POST", url:ajaxurl, params: data}).
		  success(function(data, status, headers, config) {			  
				if(data.status === "success"){
					//console.log(data.oOptions);
					if(nReplaceIndex >= 0){
						//if we're overwriting, splice it in
						self.templateList.splice(nReplaceIndex, 1, data.newTemplate);
					}else{
						//otherwise, add new template to end of list
						self.templateList.push(data.newTemplate);
					}
					$scope.templateListTableParams.reload();
					$scope.kalinsAlertManager.addAlert("Template saved successfully", "success");
				}else{
					$scope.kalinsAlertManager.addAlert("Error: " + data.status, "danger");
				}
		  }).
		  error(function(data, status, headers, config) {
		    $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
		  });
	}

	self.deleteTemplate = function(templateName){
		var confirmText = "Are you sure you want to delete " + templateName + "?";
		if(templateName === "all"){
			confirmText = "Are you sure you want to delete every template you have created?";
		}
		
		if(confirm(confirmText)){
			var indexToDelete = 0;
			
			var data = { action: 'kalins_pdf_tool_template_delete',
				templateName: templateName, 
				_ajax_nonce : deleteTemplateNonce
			}
	
			$http({method:"POST", url:ajaxurl, params: data}).
			  success(function(data, status, headers, config) {
					if(data === "success"){
						if(templateName == "all"){
							self.templateList.splice(0, self.templateList.length);
							$scope.templateListTableParams.reload();
							$scope.kalinsAlertManager.addAlert("Templates deleted successfully", "success");
						}else{
							//figure out which item to delete out of our array
							for(var i =0; i<self.templateList.length; i++){
								if(templateName === self.templateList[i]['templateName']){
									indexToDelete = i;
									break;
								}
							}
							
							self.templateList.splice(indexToDelete, 1);
	
							var currentPage = $scope.templateListTableParams.page();
							//check if our current page data is empty and if so, go to previous page
							//(even if we call reload() before this, we still check for 1.)
							if($scope.templateListTableParams.data.length === 1 && currentPage > 1){
								$scope.templateListTableParams.page(currentPage - 1);
							}
							$scope.templateListTableParams.reload();
							$scope.kalinsAlertManager.addAlert("Template deleted successfully", "success");
						}
					}else{
						$scope.kalinsAlertManager.addAlert(data.status, "danger");
					}
			  }).
			  error(function(data, status, headers, config) {
			    $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
			  });
		}
	}

	self.loadTemplate = function(oTemplate){
		self.oOptions = oTemplate;
	}

	self.addPost = function(postID){
		if(self.buildPostListByID[postID]){
			if(!confirm("This post has already been added at least once. Are you sure you want to add it again?")){
				return;
			}	
		}
		
		//loop to find the correct ID in our main postList
		for(var i = 0; i<self.postList.length; i++){
			if(self.postList[i].ID === postID){
				//copy object in case user wants to add multipes of the same page. angular won't allow duplicates. Must use angular json filter
				//to avoid copying angular's internal $$haskey that it uses to uniquely identify array elements
				var newObj = JSON.parse($filter('json')(self.postList[i]));
				self.buildPostList.push(newObj);
				
				//add count to the tracking array to support adding same post multiple times
				if(!self.buildPostListByID[postID]){
					self.buildPostListByID[postID] = 1;
				}else{
					self.buildPostListByID[postID]++;
				}
				break;
			}
		}
	}

	self.removePost = function(postID){
		//loop to find the correct ID in our main postList
		for(var i = 0; i<self.buildPostList.length; i++){
			if(self.buildPostList[i].ID === postID){
				self.buildPostList.splice(i,1);
				self.buildPostListByID[postID]--;
				break;
			}
		}
	}

	self.deleteFile = function(filename){
		var confirmText = "Are you sure you want to delete " + filename + "?";
		if(filename === "all"){
			confirmText = "Are you sure you want to delete every file you have created?";
		}
		
		if(confirm(confirmText)){
			var indexToDelete = 0;
			
			var data = { action: 'kalins_pdf_tool_delete',
				filename: filename, 
				_ajax_nonce : deleteNonce
			}
	
			$http({method:"POST", url:ajaxurl, params: data}).
			  success(function(data, status, headers, config) {
					if(data.status == "success"){
						if(filename == "all"){
							self.pdfList.splice(0, self.pdfList.length);
							$scope.pdfListTableParams.reload();
							$scope.kalinsAlertManager.addAlert("Files deleted successfully", "success");
						}else{
							//figure out which item to delete out of our array
							for(var i =0; i<self.pdfList.length; i++){
								if(filename === self.pdfList[i]['fileName']){
									indexToDelete = i;
									break;
								}
							}
							
							self.pdfList.splice(indexToDelete, 1);
	
							var currentPage = $scope.pdfListTableParams.page();
							//check if our current page data is empty and if so, go to previous page
							//(even if we call reload() before this, we still check for 1.)
							if($scope.pdfListTableParams.data.length === 1 && currentPage > 1){
								$scope.pdfListTableParams.page(currentPage - 1);
							}
							$scope.pdfListTableParams.reload();
							$scope.kalinsAlertManager.addAlert("File deleted successfully", "success");
						}
					}else{
						$scope.kalinsAlertManager.addAlert(data.status, "danger");
					}
			  }).
			  error(function(data, status, headers, config) {
			    $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
			  });
		}
	}
}]);	
</script>

	<div ng-app="kalinsPDFToolPage" ng-controller="InputController as InputCtrl" class="kContainer">	
		<h2>PDF Creation Station</h2>
		<h3>by Kalin Ringkvist - <a href="http://kalinbooks.com">kalinbooks.com</a></h3>
		<p>Create custom PDF files for any combination of posts and pages.</p>
		
		<p><a href="#" ng-click="showVideo = !showVideo">Watch a tutorial video</a></p>
		<div class="text-center" ng-show="showVideo">
		  <hr>
		  <iframe width="420" height="315" src="//www.youtube.com/embed/zLHpr-8aYVw" frameborder="0" allowfullscreen></iframe>
		  <p><a href="#" ng-click="showVideo = false">Close video</a></p>
		  <hr>
		</div>
		
		<div class="form-group text-right">
			<button class="btn btn-info" ng-click="kalinsToggles.toggleAll();">{{kalinsToggles.sToggleAll}}</button>
		</div>

		<accordion close-others="false">
	    <accordion-group is-open="kalinsToggles.aBooleans[0]">
		    <accordion-heading>
		      <div><strong>Add pages and posts</strong><k-help str="{{oHelpStrings['h_addPages']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[0], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[0]}"></i></div>
	      </accordion-heading>
				  <table ng-table="postListTableParams" show-filter="InputCtrl.postList.length > 1" class="table">
		        <tr ng-repeat="post in $data" ng-class="{'active': InputCtrl.buildPostListByID[post.ID]>0}">
		          <td data-title="'Title'" sortable="'title'" filter="{ 'title': 'text' }">
		          	{{post.title}}
		          </td>
		          <td data-title="'Date'" sortable="'date'" filter="{ 'date': 'text' }">
		            {{post.date}}
		          </td>
		          <td data-title="'Type'" sortable="'type'" filter="{ 'type': 'text' }">
		            {{post.type}}
		          </td>
		          <td data-title="'Status'" sortable="'status'" filter="{ 'status': 'text' }">
		            {{post.status}}
		          </td>
		          <td data-title="'Add'" ng-click="InputCtrl.addPost(post.ID);">
		            <button class="btn btn-success btn-xs">Add</button>
		          </td>
		        </tr>
		      </table>
			</accordion-group>
			
	    <accordion-group is-open="kalinsToggles.aBooleans[1]">
		    <accordion-heading>
		      <div><strong>My document</strong><k-help str="{{oHelpStrings['h_myDocument']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[1], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[1]}"></i></div>
	      </accordion-heading>
				<p ng-show="InputCtrl.buildPostList.length === 0">Your page list will appear here. Click an Add button above to start adding pages.</p>
				<table ng-show="InputCtrl.buildPostList.length > 0" class="table">
					<tbody ui:sortable ng-model="InputCtrl.buildPostList">
		        <tr ng-repeat="post in InputCtrl.buildPostList">
		          <td>
		          	{{post.title}}
		          </td>
		          <td>
		            {{post.date}}
		          </td>
		          <td>
		            <button class="btn btn-warning btn-xs" ng-click="InputCtrl.removePost(post.ID);">Remove</button>
		          </td>
		        </tr>
		      </tbody>
	      </table>
			</accordion-group>

	    <accordion-group is-open="kalinsToggles.aBooleans[2]">
		    <accordion-heading>
		      <div><strong>Insert HTML before every page or post</strong><k-help str="{{oHelpStrings['h_insertBefore']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[2], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[2]}"></i></div>
	      </accordion-heading>
        <b>HTML to insert before every page:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.beforePage"></textarea>
        <b>HTML to insert before every post:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.beforePost"></textarea>
	    </accordion-group>

	    <accordion-group is-open="kalinsToggles.aBooleans[3]">
		    <accordion-heading>
		      <div><strong>Insert HTML after every page or post</strong><k-help str="{{oHelpStrings['h_insertAfter']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[3], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[3]}"></i></div>
	      </accordion-heading>
        <b>HTML to insert after every page:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.afterPage"></textarea>
        <b>HTML to insert after every post:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.afterPost"></textarea>
	    </accordion-group>

	    <accordion-group is-open="kalinsToggles.aBooleans[4]">
		    <accordion-heading>
		      <div><strong>Insert HTML for title and final pages</strong><k-help str="{{oHelpStrings['h_insertTitleFinal']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[4], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[4]}"></i></div>
	      </accordion-heading>
        <b>HTML to insert for title page:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.titlePage"></textarea>
        <b>HTML to insert for final page:</b><br />
        <textarea class="form-control" rows='3' ng-model="InputCtrl.oOptions.finalPage"></textarea>
	    </accordion-group>
	    
	    <accordion-group is-open="kalinsToggles.aBooleans[5]">
		    <accordion-heading>
		      <div><strong>Create Files</strong><k-help str="{{oHelpStrings['h_toolOptions']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[5], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[5]}"></i></div>
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
			    
			    <div class="row">
				    <div class="form-group col-md-6 col-xs-12" >
				    	<div class="checkbox col-md-offset-2">
				    		<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.autoPageBreak"></input> Automatic page breaks</label>
				    	</div>
				    	<div class="checkbox col-md-offset-2">
				      	<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.includeTOC"></input> Include Table of Contents</label>
				      </div>
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
				      <k-help str="{{oHelpStrings['h_convertLinks']}}"></k-help><b> Convert videos to links:</b>
				      <div class="checkbox">
				      	<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.convertYoutube"></input> YouTube</label>
				      </div>
				      
					    <div class="checkbox">
				       	<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.convertVimeo"></input> Vimeo</label>
				      </div>
					    <div class="checkbox"> 
				       	<label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.convertTed"></input> Ted Talks</label>
				      </div>
				    </div>
			    </div>
			    
			    <div class="row">
					  <div class="form-group col-xs-12" >
				      <label>File name: <input type="text" class="form-control k-inline-text-input" ng-model="InputCtrl.oOptions.filename" ></input></label>
							&nbsp;
				      <label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.bCreatePDF"></input> .pdf </label>
							&nbsp;|&nbsp;
				      <label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.bCreateHTML"></input> .html </label>
							&nbsp;|&nbsp;
				      <label><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.bCreateTXT"></input> .txt </label>
				      <k-help str="{{oHelpStrings['h_filenameTypes']}}"></k-help>
						</div>
					</div>

					<div class="row">
				    <div class="form-group text-center">
				      <button ng-click="InputCtrl.createDocument();" class="btn btn-success">Create Documents!</button>
				      <button ng-click='InputCtrl.resetToDefaults();' class="btn btn-warning">Reset Defaults</button>
				    </div>
				    		    	<hr>
				    <div class="form-group text-center">
		    	    <label>Name: <input type="text" class="form-control k-inline-text-input" ng-model="InputCtrl.sNewTemplateName" ></input></label>
		    	    &nbsp;
		    	    <button ng-click='InputCtrl.saveTemplate();' class="btn btn-success">Save as Template</button> <k-help str="{{oHelpStrings['h_savedTemplates']}}"></k-help>
		    	  </div>
			    </div>
			    
			    <div class="row">
		      	<div class="col-md-offset-1 col-md-10">
					  	<alert ng-repeat="alert in kalinsAlertManager.aAlerts" type="{{alert.type}}" close="kalinsAlertManager.closeAlert($index)">{{alert.index}} - {{alert.msg}}</alert>
					  </div>
					</div>
		    </form>
	    </accordion-group>

	    <accordion-group is-open="kalinsToggles.aBooleans[6]">
		    <accordion-heading>
		      <div><strong>Existing Files</strong><k-help str="{{oHelpStrings['h_existingFiles']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[6], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[6]}"></i></div>
	      </accordion-heading>
	    	<p ng-show="InputCtrl.pdfList.length === 0">You do not have any created files.</p>
		    <div ng-show="InputCtrl.pdfList.length > 0">
		    	<button ng-click="InputCtrl.deleteFile('all');" class="btn btn-danger">Delete all</button>
	        <table ng-table="pdfListTableParams" show-filter="InputCtrl.pdfList.length > 1" class="table">
	          <tr ng-repeat="file in $data">
	            <td data-title="'Name'" sortable="'fileName'" filter="{ 'fileName': 'text' }">
	            	<a href="{{InputCtrl.pdfUrl + file.fileName}}" target="_blank">{{file.fileName}}</a>
	            </td>
	            <td data-title="'Date'" sortable="'date'" filter="{ 'date': 'text' }">
	              {{file.date}}
	            </td>
	            <td data-title="'Delete'">
	             	<button ng-click="InputCtrl.deleteFile(file.fileName);" class="btn btn-warning btn-xs">Delete</button>
	            </td>
	          </tr>
	        </table>
		    </div>
	    </accordion-group>
	    
	    <accordion-group is-open="kalinsToggles.aBooleans[7]">
		    <accordion-heading>
		      <div><strong>Saved Document Templates</strong><k-help str="{{oHelpStrings['h_savedTemplates']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[7], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[7]}"></i></div>
	      </accordion-heading>
	    	<p ng-show="InputCtrl.templateList.length === 0">Type a name in the box and hit Save as Template to save your current document as your first template.</p>
		    <div ng-show="InputCtrl.templateList.length > 0">
		    	<div class="form-group">
	          <button ng-click="InputCtrl.deleteTemplate('all');" class="btn btn-danger">Delete all</button>
	        </div>
	        
	        <table ng-table="templateListTableParams" show-filter="InputCtrl.templateList.length > 1" class="table">
	          <tr ng-repeat="template in $data">
	            <td data-title="'Name'" sortable="'templateName'" filter="{ 'templateName': 'text' }">
	            	{{template.templateName}}
	            </td>
	            <td data-title="'Date'" sortable="'date'" filter="{ 'date': 'text' }">
	              {{template.date}}
	            </td>
	            <td data-title="'Load'">
	             	<button ng-click="InputCtrl.loadTemplate(template);" class="btn btn-success btn-xs">Load</button>
	            </td>
	            <td data-title="'Delete'">
	             	<button ng-click="InputCtrl.deleteTemplate(template.templateName);" class="btn btn-warning btn-xs">Delete</button>
	            </td>
	          </tr>
	        </table>
		    </div>
	    </accordion-group>
	    
	
	    <accordion-group is-open="kalinsToggles.aBooleans[8]">
		    <accordion-heading>
		      <div><strong>Shortcodes</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[8], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[8]}"></i></div>
	      </accordion-heading>
	    	<b>Blog shortcodes:</b> Use these codes anywhere in the above form to insert information about your blog.
	    	<p><ul>
	        <li><b>[current_time format="m-d-Y"]</b> -  PDF creation date/time <b>*</b></li>
	        <li><b>[blog_name]</b> -  the name of the blog</li>
	        <li><b>[blog_description]</b> - description of the blog</li>
	        <li><b>[blog_url]</b> - blog base url</li>
	        </ul>
	        </p><br />
	        
	        <b>Page/post Shortcodes:</b> Use these codes before and after posts and pages<br />
	        <p>
	        <ul>
	        <li><b>[ID]</b> - the ID number of the page/post</li>
	        <li><b>[post_author type="display_name"]</b> - post author information. Possible types: ID, user_login, user_pass, user_nicename, user_email, user_url, display_name, user_firstname, user_lastname, nickname, description, primary_blog</li>
	        <li><b>[post_permalink]</b> - the page permalink</li>
	        <li><b>[post_date format="m-d-Y"]</b> - date page/post was created <b>*</b></li>
	        <li><b>[post_date_gmt format="m-d-Y"]</b> - date page/post was created in gmt time <b>*</b></li>
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
	    
	    <accordion-group is-open="kalinsToggles.aBooleans[9]">
		    <accordion-heading>
		      <div><strong>About</strong><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[9], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[9]}"></i></div>
	      </accordion-heading>
	    	<p>Thank you for using PDF Creation Station. To report bugs, request help or suggest features, visit <a href="http://kalinbooks.com/pdf-creation-station/" target="_blank">KalinBooks.com/pdf-creation-station</a>. If you find this plugin useful, please consider <A href="http://wordpress.org/extend/plugins/kalins-pdf-creation-station/">rating this plugin on WordPress.org</A> or making a PayPal donation:</p>
				<p>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="C6KPVS6HQRZJS">
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="Donate to Kalin Ringkvist's WordPress plugin development.">
					<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
				</p><br/>
	      <p>You may also like <a href="http://kalinbooks.com/easy-edit-links-wordpress-plugin/" target="_blank">Kalin's Easy Edit Links</a> - <br /> Adds a box to your page/post edit screen with links and edit buttons for all pages, posts, tags, categories, and links for convenient edit-switching and internal linking.</p>
	      <p>Or <a href="http://kalinbooks.com/post-list-wordpress-plugin/" target="_blank">Kalin's Post List</a> - <br /> Use a shortcode in your posts to insert dynamic, highly customizable lists of posts, pages, images, or attachments based on categories and tags. Works for table-of-contents pages or as a related posts plugin.</p>
	    </accordion-group>

    </accordion>
	</div>
</html>