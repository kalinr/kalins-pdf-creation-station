<?php
	
	if ( !function_exists( 'add_action' ) ) {
		echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
		exit;
	}
		
	kalinsPDF_createPDFDir();//make sure our PDF dir exists
	
	$create_nonce = wp_create_nonce( 'kalins_pdf_tool_create' );
	$delete_nonce = wp_create_nonce( 'kalins_pdf_tool_delete' );
	$reset_nonce = wp_create_nonce( 'kalins_pdf_tool_reset' );
	
	$adminOptions = kalins_pdf_get_tool_options();

	if(defined("KALINS_PDF_POST_ORDER")){
		$customList = get_posts('numberposts=-1&post_type=any&orderby=' .KALINS_PDF_POST_ORDER_BY ."&order=" .KALINS_PDF_POST_ORDER);
		$postList = get_posts('numberposts=-1&orderby=' .KALINS_PDF_POST_ORDER_BY ."&order=" .KALINS_PDF_POST_ORDER);
	}else{
		$customList = get_posts('numberposts=-1&post_type=any');
		$postList = get_posts('numberposts=-1');
	}
	
	$pageList = get_pages();
	
	$l = count($customList);
	for($i=$l - 1; $i >= 0; $i--){//loop to remove all posts, pages and attachments from our custom list so we can have all custom types in the same array
		if($customList[$i]->post_type == "post" || $customList[$i]->post_type == "attachment" || $customList[$i]->post_type == "page"){
			unset($customList[$i]);
		}
	}
	$customList = array_values($customList);//recreate the array because somehow unset() doesn't re-index the array
	
	$pdfList = array();
	$count = 0;
	
	//$uploads = wp_upload_dir();
	//$pdfDir = $uploads['basedir'] .'/kalins-pdf/';
	$pdfDir = KALINS_PDF_DIR;
	
	//$pdfURL = $uploads['baseurl'] .'/kalins-pdf/';
	
	$pdfURL = KALINS_PDF_URL;
	
	if ($handle = opendir($pdfDir)) {

		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && substr($file, stripos($file, ".")+1, 3) == "pdf") {//loop to find all relevant files 
				$fileObj = new stdClass();
				$fileObj->fileName = $file;
				$fileObj->date = date("Y-m-d H:i:s", filemtime($pdfDir .$file));
				$pdfList[$count] = $fileObj;//compile array of file information simply to pass to javascript
				$count++;
			}
		}
		closedir($handle);
	}
	
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

var app = angular.module('kalinsPDFToolPage', ['ngTable']);

//TODO: turn this into a module in separate file so we don't repeat this code on the settings page
app.controller("UIController",["$scope", function($scope) {
	var self = this;
	self.aCollapsed = [false, false, false, false, false, false, false, false];//list of states for the main divs
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
		if(nStateCount > 4){
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


app.controller("InputController",["$scope", "$http", "$filter", "ngTableParams", function($scope, $http, $filter, ngTableParams) {
	var self = this;

	self.pdfList = <?php echo json_encode($pdfList);//hand over the objects and vars that javascript will need?>;            
  $scope.tableParams = new ngTableParams({
    page: 1,            // show first page
    count: 10,          // count per page
    filter: {
      fileName: ''       // initial filter
    },
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
	
	var pageList = <?php echo json_encode($pageList);?>;
	var postList = <?php echo json_encode($postList); ?>;
	var customList = <?php echo json_encode($customList); ?>;
	var createNonce = '<?php echo $create_nonce; //pass a different nonce security string for each possible ajax action?>'
	var deleteNonce = '<?php echo $delete_nonce; ?>';
	var resetNonce = '<?php echo $reset_nonce; ?>';

	self.oOptions = <?php echo json_encode($adminOptions); ?>;

	self.resetToDefaults = function(){		
		if(confirm("Are you sure you want to reset all of your field values? You will lose all the information you have entered into the form. (This will NOT delete or change your existing PDF documents.)")){
			var data = { action: 'kalins_pdf_tool_defaults', _ajax_nonce : resetNonce};

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

	self.createDocument = function(sortString){
		angular.element('#sortDialog').dialog('close');
		
		var data = JSON.parse( JSON.stringify( self.oOptions ) );
		data.action = 'kalins_pdf_tool_create';//tell wordpress what to call
		data._ajax_nonce = createNonce;//authorize it

		if(sortString){
			data.pageIDs = sortString;
		}else{
			data.pageIDs = angular.element("#sortable").sortable('toArray').join(",");
		}
		self.sCreateStatus = "Building PDF file. Wait time will depend on the length of the document, image complexity and current server load. Refreshing the page or navigating away will cancel the build.";
		
		$http({method:"POST", url:ajaxurl, params: data}).
		  success(function(data, status, headers, config) {			  			
			  var startPosition = data.indexOf("{")
				var responseObjString = data.substr(startPosition, data.lastIndexOf("}") - startPosition + 1);
				var newFileData = JSON.parse(responseObjString);
				if(newFileData.status == "success"){		
					self.pdfList.push(newFileData);
					$scope.tableParams.reload();
					self.sCreateStatus = "File created successfully";	
				}else{
					self.sCreateStatus = "Error: " + newFileData.status;
				}
		  }).
		  error(function(data, status, headers, config) {
		    self.sCreateStatus = "An error occurred: " + data;
		  });
	}

	self.createNow = function() {
		
		var sortString = '';

		//TODO: pageCount var isn't necessary here. can use sortString.length
		//TODO: angular.element is basically jQuery. We should be binding these values with angular
		var pageCount = 0;
		var l = pageList.length;		   
		for(var i=0; i<l; i++){
			if(angular.element('#chk' + pageList[i]['ID']).is(':checked')){
				sortString += 'pg_' + pageList[i]['ID'] + ",";
				pageCount++;
			}
		}
		
		var l = customList.length;		   
		for(var i=0; i<l; i++){
			if(angular.element('#chk' + customList[i]['ID']).is(':checked')){
				sortString += 'po_' + customList[i]['ID'] + ",";
				pageCount++;
			}
		}

		var l = postList.length;		   
		for(var i=0; i<l; i++){
			if(angular.element('#chk' + postList[i]['ID']).is(':checked')){
				sortString += 'po_' + postList[i]['ID'] + ",";
				pageCount++;
			}
		}
		
		if(pageCount == 0){
			self.sCreateStatus = "Error: you must select at least one page or post to create a PDF.";
			return;
		}
		
		sortString = sortString.substr(0, sortString.length - 1);
		self.createDocument(sortString);
	};
	
	self.deleteFile = function(filename){
		var indexToDelete = 0;
		
		var data = { action: 'kalins_pdf_tool_delete',
			filename: filename, 
			_ajax_nonce : deleteNonce
		}

		$http({method:"POST", url:ajaxurl, params: data}).
		  success(function(data, status, headers, config) {
				var newFileData = JSON.parse(data.substr(0, data.lastIndexOf("}") + 1));
				if(newFileData.status == "success"){
					if(filename == "all"){
						self.pdfList.splice(0, $scope.tableParams.data.length);
						$scope.tableParams.reload();
						self.sCreateStatus = "Files deleted successfully";
					}else{

						//figure out which item to delete out of our array
						for(var i =0; i<self.pdfList.length; i++){
							if(filename === self.pdfList[i]['fileName']){
								indexToDelete = i;
								break;
							}
						}
						
						self.pdfList.splice(indexToDelete, 1);

						var currentPage = $scope.tableParams.page();
						//check if our current page data is empty and if so, go to previous page
						//(even if we call reload() before this, we still check for 1.)
						if($scope.tableParams.data.length === 1 && currentPage > 1){
							$scope.tableParams.page(currentPage - 1);
						}
						$scope.tableParams.reload();
						self.sCreateStatus = "File deleted successfully";
					}
				}else{
					self.sCreateStatus = newFileData.status;
				}
		  }).
		  error(function(data, status, headers, config) {
		    self.sCreateStatus = "An error occurred: " + data;
		  });
	}
}]);





jQuery(document).ready(function($){
	var pdfList = <?php echo json_encode($pdfList);//hand self.pdfListe objects and vars that javascript will need?>;
	var pageList = <?php echo json_encode($pageList);?>;
	var postList = <?php echo json_encode($postList); ?>;
	var customList = <?php echo json_encode($customList); ?>;
	var createNonce = '<?php echo $create_nonce; //pass a different nonce security string for each possible ajax action?>'
	var deleteNonce = '<?php echo $delete_nonce; ?>';
	
	var selectAllPageState = true;
	var selectAllPostState = true;
	
	$('#btnSelectAllPages').click(function() {
		var l = pageList.length;
		for(var i=0; i<l; i++){
			$('#chk' + pageList[i]['ID']).attr('checked', selectAllPageState);	
		}
		
		selectAllPageState = !selectAllPageState;
	});
	
	$('#btnSelectAllPosts').click(function() {
		var l = postList.length; 
		for(var i=0; i<l; i++){
			$('#chk' + postList[i]['ID']).attr('checked', selectAllPostState);
		}
		
		l = customList.length;
		for(var i=0; i<l; i++){
			$('#chk' + customList[i]['ID']).attr('checked', selectAllPostState);	
		}
		
		selectAllPostState = !selectAllPostState;
	});
	
	$('#btnCreateCancel').click(function(){
		$('#sortDialog').dialog('close');									 
	});
	
	$(function() {
		
		$('#sortDialog').dialog({
			autoOpen: false,
			hide: 'explode',
			width: 370,
			resizable:false,
			modal: true
		});
			
		$('#btnOpenDialog').click(function() {
			
			var sortHTML = '<ul id="sortable">';
			var pageCount = 0;
			var l = pageList.length;		   
			for(var i=0; i<l; i++){
				if($('#chk' + pageList[i]['ID']).is(':checked')){
					sortHTML += '<li class="ui-state-default" id="pg_' + pageList[i]['ID'] + '"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' + pageList[i].post_title + '</li>';
					pageCount++;
				}
			}
	
			var l = customList.length;		   
			for(var i=0; i<l; i++){
				if($('#chk' + customList[i]['ID']).is(':checked')){
					sortHTML += '<li class="ui-state-default" id="po_' + customList[i]['ID'] + '"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' + customList[i].post_title + '</li>';
					pageCount++;
				}
			}
	
			var l = postList.length;		   
			for(var i=0; i<l; i++){
				if($('#chk' + postList[i]['ID']).is(':checked')){
					sortHTML += '<li class="ui-state-default" id="po_' + postList[i]['ID'] + '"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' + postList[i].post_title + '</li>';
					pageCount++;
				}
			}
			
			if(pageCount == 0){
				$('#createStatus').html("Error: you must select at least one page or post to create a PDF.");
				return;
			}
			
			sortHTML += '</ul>';
			$('#sortHolder').html(sortHTML);
						
			$(function() {//set the div as sortable every time we open the dialog (doing this earlier and just calling refresh didn't work)
				$("#sortable").sortable();
				$("#sortable").disableSelection();
			});
		
			$('#sortDialog').dialog('open');
			return false;
		});
	});
});
	
</script>

<div ng-app="kalinsPDFToolPage" ng-controller="UIController as UICtrl">
	<div ng-controller="InputController as InputCtrl">

		<h2>PDF Creation Station</h2>

		<h3>by Kalin Ringkvist - kalinbooks.com</h3>

		<p>Create custom PDF files for any combination of posts and pages.</p>

		<div class="collapse" ng-click="UICtrl.toggleAll()"><b>{{UICtrl.sToggleAll}}</b></div>
		
		<div class="collapse" ng-click="UICtrl.toggleCollapsed(0)"><b>Select Pages and Posts</b></div>
		<div class="wideHolder" ng-hide="UICtrl.aCollapsed[0]">
		    <div class='formDiv'>
		        <button id="btnSelectAllPages">Select All</button> Pages:<br/><br/>
		        <?php
		            $l = count($pageList);
					$indent = '';
		            $previousIndent = '';
		            $previousID = 0;
		            for($i=0; $i<$l; $i++){//build our list of pages with checkboxes
					
		                $pageID = $pageList[$i]->ID;
		                $parent = $pageList[$i]->post_parent;
		                
		                if($parent == 0){//if this is a top level page, don't indent
		                    $indent = '';
		                }else{
		                    if($parent == $previousID){//if the parent is the previous page, add another three spaces of indentation (if pages are not returned by wordpress in proper order, indentation will fail)"
		                        $indent = $previousIndent .'&nbsp;&nbsp;&nbsp;';
		                    }
		                }
		                $previousID = $pageID;
		                $previousIndent = $indent;
		                echo($indent .'<input type=checkbox id="chk' .$pageID .'" name="chk' .$pageID .'"></ input> ' .$pageList[$i]->post_title .'<br />');//create each checkbox and label
		            }
					
		        ?>
		    </div>
		
		    <div class="formDiv">
		    	<button id="btnSelectAllPosts">Select All</button> Posts:<br/><br/>	
		        <?php
		            $l = count($postList);
		            for($i=0; $i<$l; $i++){//build our list of posts with checkboxes
		                $pageID = $postList[$i]->ID;
		                //echo $postList[$i]->post_parent;
		                echo('<input type=checkbox id="chk' .$pageID .'" name="chk' .$pageID .'"></ input> ' .$postList[$i]->post_title .'<br />');
		            }
					
					$l = count($customList);
					
					if($l > 0){//if we have something in our list of custom pages, echo the section then loop to add each one
						echo "<hr/> Custom types: <br/>";
						for($i=0; $i<$l; $i++){//build our list of posts with checkboxes
							$pageID = $customList[$i]->ID;
							echo('<input type=checkbox id="chk' .$pageID .'" name="chk' .$pageID .'"></ input> ' .$customList[$i]->post_title .'<br />');
						}
					}
		        ?>
		    </div>
		</div>
		
		<div class="collapse" ng-click="UICtrl.toggleCollapsed(1)"><b>Insert HTML before every page or post</b></div>
		   <div class="txtfieldHolder" ng-hide="UICtrl.aCollapsed[1]">
		        <div class="textAreaDiv">
		            <b>HTML to insert before every page:</b><br />
		            <textarea class="txtArea" name='txtBeforePage' id='txtBeforePage' rows='8' ng-model="InputCtrl.oOptions.beforePage"></textarea>
		        </div>
		        <div class="textAreaDiv">
		            <b>HTML to insert before every post:</b><br />
		            <textarea class="txtArea" name='txtBeforePost' id='txtBeforePost' rows='8' ng-model="InputCtrl.oOptions.beforePost"></textarea>
		        </div>
		    </div>
		    <div class="collapse" ng-click="UICtrl.toggleCollapsed(2)"><b>Insert HTML after every page or post</b></div>
		    <div class="txtfieldHolder" ng-hide="UICtrl.aCollapsed[2]">
		        <div class="textAreaDiv">
		            <b>HTML to insert after every page:</b><br />
		            <textarea class="txtArea" name='txtAfterPage' id='txtAfterPage' rows='8' ng-model="InputCtrl.oOptions.afterPage"></textarea>
		        </div>
		        <div class="textAreaDiv">
		            <b>HTML to insert after every post:</b><br />
		            <textarea class="txtArea" name='txtAfterPost' id='txtAfterPost' rows='8' ng-model="InputCtrl.oOptions.afterPost"></textarea>
		        </div>
		    </div>
		    <div class="collapse" ng-click="UICtrl.toggleCollapsed(3)"><b>Insert HTML for title and final pages</b></div>
		    <div class="txtfieldHolder" ng-hide="UICtrl.aCollapsed[3]">
		        <div class="textAreaDiv">
		            <b>HTML to insert for title page:</b><br />
		            <textarea class="txtArea" name='txtTitlePage' id='txtTitlePage' rows='8' ng-model="InputCtrl.oOptions.titlePage"></textarea>
		        </div>
		        <div class="textAreaDiv">
		            <b>HTML to insert for final page:</b><br />
		            <textarea class="txtArea" name='txtFinalPage' id='txtFinalPage' rows='8' ng-model="InputCtrl.oOptions.finalPage"></textarea>
		        </div>
		    </div>
		    <div class="collapse" ng-click="UICtrl.toggleCollapsed(4)"><b>CREATE PDF!</b></div>
		    <div class="generalHolder" ng-hide="UICtrl.aCollapsed[4]">
		        <p>Header title: <input type='text' name='txtHeaderTitle' id='txtHeaderTitle' class='txtHeader' ng-model="InputCtrl.oOptions.headerTitle"></input></p>
		        <p>Header sub title: <input type='text' name='txtHeaderSub' id='txtHeaderSub' class='txtHeader' ng-model="InputCtrl.oOptions.headerSub"></input></p><br/>
		        
		         <p><input type='checkbox' id='chkIncludeImages' name='chkIncludeImages' ng-model="InputCtrl.oOptions.includeImages"></input> Include Images &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type="text" id="txtFontSize" size="2" maxlength="3" ng-model="InputCtrl.oOptions.fontSize" /> Content font size &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type='checkbox' id='chkRunShortcodes' name='chkRunShortcodes' ng-model="InputCtrl.oOptions.runShortcodes"></input> Run other plugin shortcodes, &nbsp;<input type='checkbox' id='chkRunFilters' name='chkRunFilters' ng-model="InputCtrl.oOptions.runFilters"></input> and content filters</p>
		         
		         <p>Convert videos to links: &nbsp;&nbsp;<input type='checkbox' id='chkConvertYoutube' name='chkConvertYoutube' ng-model="InputCtrl.oOptions.convertYoutube"></input> YouTube, &nbsp;<input type='checkbox' id='chkConvertVimeo' name='chkConvertVimeo' ng-model="InputCtrl.oOptions.convertVimeo"></input> Vimeo, &nbsp;<input type='checkbox' id='chkConvertTed' name='chkConvertTed' ng-model="InputCtrl.oOptions.convertTed"></input> Ted Talks</p>
		         
		         <br/>
		        
		        File name: <input type="text" name='txtFileName' id='txtFileName' ng-model="InputCtrl.oOptions.filename" ></input>.pdf  &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp; <input type='checkbox' id='chkAutoPageBreak' name='chkAutoPageBreak' ng-model="InputCtrl.oOptions.autoPageBreak"></input> Automatic page breaks &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp; <input type='checkbox' id='chkIncludeTOC' name='chkIncludeTOC' ng-model="InputCtrl.oOptions.includeTOC"></input> Include Table of Contents
		        </p>
		        <p align="center"><br />
		        <button id="btnOpenDialog">Create PDF!</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button type='button' ng-click='InputCtrl.resetToDefaults();'>Reset Defaults</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a name="createNow" id="createNow" href="javascript:void(0);" ng-click="InputCtrl.createNow();" title="Use this if the 'Create PDF!' button won't properly show the popup. You won't be able to re-order your pages, but at least you can create a document.">create now!</a></p>
		        <p align="center"><span id="createStatus">{{InputCtrl.sCreateStatus}}</span></p>
		        
		    </div>
		    <div class="collapse" ng-click="UICtrl.toggleCollapsed(5)"><b>Existing PDF Files</b></div>
		    <div class="generalHolder" id="pdfListDiv" ng-hide="UICtrl.aCollapsed[5]">
		    	<p ng-show="InputCtrl.pdfList.length === 0">You have not created any PDF files yet.</p>
			    <div ng-show="InputCtrl.pdfList.length > 0">
			    	<button ng-click="InputCtrl.deleteFile('all');">Delete all</button>
		        <table ng-table="tableParams" show-filter="InputCtrl.pdfList.length > 1" class="table">
		          <tr ng-repeat="user in $data">
		            <td data-title="'Name'" sortable="'fileName'" filter="{ 'fileName': 'text' }">
		            	<a href="<?php echo $pdfURL; ?>{{user.fileName}}">{{user.fileName}}</a>
		            </td>
		            <td data-title="'Date'" sortable="'date'">
		              {{user.date}}
		            </td>
		            <td data-title="'Delete'">
		             	<button ng-click="InputCtrl.deleteFile(user.fileName, $index);">Delete</button>
		            </td>
		          </tr>
		        </table>
			    </div>
		    </div>

		    <div class="collapse" ng-click="UICtrl.toggleCollapsed(6)"><b>Shortcodes</b></div>
		    <div class="generalHolder" ng-hide="UICtrl.aCollapsed[6]">
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
		    
		    </div>
		    <div class="collapse" ng-click="UICtrl.toggleCollapsed(7)"><b>About</b></div>
		    <div class="generalHolder" ng-hide="UICtrl.aCollapsed[7]">
		    
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
		        
		    </div>
		    
		    <div id="sortDialog" title="Adjust Order and Create"><div id="sortHolder" class="sortHolder"></div><p align="center"><br /><button id="btnCreateCancel">Cancel</button>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<button ng-click="InputCtrl.createDocument();">Create PDF!</button></p></div>	
	</div>
</div>
</html>