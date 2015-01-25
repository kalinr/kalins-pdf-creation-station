<?php
  
  if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
  }
  
  //TODO: is this how we add a pdf to the uploads library?
  //$return = apply_filters( 'wp_handle_upload', array( 'file' => $new_file, 'url' => $url, 'type' => $type ) );
  //found this in add-from-server plugin
  
    
  kalinsPDF_createPDFDir();//make sure our PDF dir exists
  
  $create_nonce = wp_create_nonce( 'kalins_pdf_tool_create' );
  $save_nonce = wp_create_nonce( 'kalins_pdf_tool_save' );
  $delete_template_nonce = wp_create_nonce( 'kalins_pdf_tool_template_delete' );
  $delete_nonce = wp_create_nonce( 'kalins_pdf_tool_delete' );
    
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
    $newItem->menu_order = $allPostList[$i]->menu_order;
    $newItem->date = substr($allPostList[$i]->post_date, 0, strpos($allPostList[$i]->post_date, " "));
    $newItem->status = $allPostList[$i]->post_status;
    $newItem->author = get_the_author_meta("display_name", $allPostList[$i]->post_author);
    
    $newItem->cats = "";//create empty properties for our cats and tags
    $newItem->tags = "";
    
    //don't get categories and tags for pages
    if($newItem->type != "page"){
      //get the category IDs
      $post_categories = wp_get_post_categories( $newItem->ID  );
      $cats = array();
      
      //loop through every category and retrieve its actual name, concatenating it to the string
      foreach($post_categories as $c){
        $cat = get_category( $c );  
        $newItem->cats .= $cat->name .", ";
      }
      
      //trim the extra ', '
      $newItem->cats = rtrim($newItem->cats, ', ');
      
      //get our tags
      $post_tags = wp_get_post_tags( $newItem->ID  );
      $tags = array();
      
      //loop through all the tags and retrive their actual name, concatenating it to the string
      foreach($post_tags as $t){
        //$tag = get_tags( $t );
        $newItem->tags .= $t->name .", ";
      }
      
      //trim the extra ', '
      $newItem->tags = rtrim($newItem->tags, ', ');
    }
    
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
  $templateOptions = kalins_pdf_get_options( KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME );
  
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
"use strict";

var app = angular.module('kalinsPDFToolPage', ['ngTable', 'ui.sortable', 'ui.bootstrap', 'kalinsUI']);

app.controller("InputController",["$scope", "$http", "$filter", "ngTableParams", "kalinsToggles", "kalinsAlertManager", function($scope, $http, $filter, ngTableParams, kalinsToggles, kalinsAlertManager) {
  //TODO: angular's ui-sortable actually uses jQuery. replace it with what we find here to eliminate jQuery:
  //http://amnah.net/2014/02/18/how-to-set-up-sortable-in-angularjs-without-jquery/
  
  //build our toggle manager for the accordion and post list show/hide options. Includes "all" button management and local storage.
  $scope.kalinsToggles = new kalinsToggles([true, true, true, true, true, true, true, true, true, true], "Close All", "Open All", "kalinsToolPageAccordionToggles");
  $scope.oPostToggles = new kalinsToggles([false, true, false, true, true, false, false, false], "Show None", "Show All", "kalinsToolPagePostViewToggles");

  //set up the alerts that show under the form buttons
  $scope.kalinsAlertManager = new kalinsAlertManager(4);

  $scope.oHelpStrings = <?php echo $toolStrings ?>;
  
  var self = this;
  var createNonce = '<?php echo $create_nonce; //pass a different nonce security string for each possible ajax action?>'
  var saveNonce = '<?php echo $save_nonce; ?>';
  var deleteTemplateNonce = '<?php echo $delete_template_nonce; ?>';
  var deleteNonce = '<?php echo $delete_nonce; ?>';
  
  self.pdfUrl = "<?php echo KALINS_PDF_URL; ?>";
  self.pdfList = <?php echo json_encode($pdfList); ?>;
  self.postList = <?php echo json_encode($customList); ?>;  
  self.sCurTemplate = "<?php echo $templateOptions->sCurTemplate; ?>";
  self.templateList = <?php echo json_encode($templateOptions->aTemplates); ?>;
  self.oOptions = {};//this is our currently active set of options that populates the entire page. It gets populated via loadTemplate()
  
  self.buildPostListByID = [];//associative array by postID to track which rows to highlight

  //setup for our page/post list table
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

  //setup for our document list table
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

  //setup for our template list table
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

  self.createDocument = function(){
    if(self.oOptions.buildPostList.length === 0){
      $scope.kalinsAlertManager.addAlert("Error: You need to add at least one page or post.", "danger");
      return;
    }

    if(!self.oOptions.bCreatePDF && !self.oOptions.bCreateHTML && !self.oOptions.bCreateTXT){
      $scope.kalinsAlertManager.addAlert("Error: You need to select at least one filetype: .pdf, .html or .txt.", "danger");
      return;
    }

    var data = {};
    data.oOptions = self.oOptions;
    data.pageIDs = "";
    
    //loop to compile a string of pa_ and po_ that tells php which pages/posts to compile and whether to treat them as a page or post
    for(var i = 0; i < self.oOptions.buildPostList.length; i++){
      if(self.oOptions.buildPostList[i].type === "page"){
        data.pageIDs = data.pageIDs + "pa_" + self.oOptions.buildPostList[i].ID;
      }else{
        data.pageIDs = data.pageIDs + "po_" + self.oOptions.buildPostList[i].ID;
      }
      if(i < self.oOptions.buildPostList.length - 1){
        data.pageIDs += ",";
      }
    }
    $scope.kalinsAlertManager.addAlert("Building PDF file. Wait time will depend on the length of the document, image complexity and current server load. Refreshing the page or navigating away will cancel the build.", "success");
    
    $http({method:"POST", url:ajaxurl, params: {"action":'kalins_pdf_tool_create', "_ajax_nonce":createNonce },  data: data}).
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
          if(data.status){
            $scope.kalinsAlertManager.addAlert("Error: " + data.status, "danger");
          }else{
            $scope.kalinsAlertManager.addAlert("Unknown Error: <br/>" + data, "danger");
          }
        }
      }).
      error(function(data, status, headers, config) {
        $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
      });
  }

  self.getTemplateIndexByName = function(sTemplateName){
    //loop through all our templates
    var l = self.templateList.length;
    for(var i= 0; i<l; i++){
      if( self.templateList[i].templateName === sTemplateName ){
        return i;//return the index of the one we find
      }
    }
    return -1;
  }

  self.saveTemplate = function(){
    var l = self.templateList.length,
    outData = {},
    nReplaceIndex = self.getTemplateIndexByName( self.oOptions.templateName );

    if(self.oOptions.templateName === ""){
      $scope.kalinsAlertManager.addAlert("Error: You need to enter a name for your template.", "danger");
      return;
    }

    //if we already have one, ask user if they really want to overwrite it
    if(nReplaceIndex >= 0){
      if(!confirm("You already have a template named " + self.oOptions.templateName + ". Would you like to overwrite it?" )){
        return;
      }
    }

    outData.pageIDs = "";
    //copy our main object so we can mess with it
    outData.oOptions = JSON.parse($filter('json')( self.oOptions ));
    outData.oOptions.buildPostList = self.oOptions.buildPostList;
    outData.oOptions.templateName = self.oOptions.templateName;
        
    $http({method:"POST", url:ajaxurl, params: {action:'kalins_pdf_tool_save', _ajax_nonce:saveNonce },  data:outData}).
      success(function(inData, status, headers, config) {        
        if(inData.status === "success"){
          if(nReplaceIndex >= 0){
            //if we're overwriting, splice it in
            self.templateList.splice(nReplaceIndex, 1, inData.newTemplate);
          }else{
            //otherwise, add new template to end of list
            self.templateList.push(inData.newTemplate);
          }
          $scope.templateListTableParams.reload();
          $scope.kalinsAlertManager.addAlert("Template saved successfully", "success");
        }else{
          $scope.kalinsAlertManager.addAlert("Error: " + inData, "danger");
        }
      }).
      error(function(inData, status, headers, config) {
        $scope.kalinsAlertManager.addAlert("An error occurred: " + inData, "danger");
      });
  }

  self.deleteTemplate = function(templateName){
    var confirmText = "Are you sure you want to delete " + templateName + "?";
    if(templateName === "all"){
      confirmText = "Are you sure you want to delete every template you have created? (This will not delete the original defaults template.)";
    }
    
    if(confirm(confirmText)){
      var indexToDelete = 0;
      
      var data = {
        templateName: templateName
      }
  
      $http({method:"POST", url:ajaxurl, params: {action:'kalins_pdf_tool_template_delete', _ajax_nonce:deleteTemplateNonce },  data:data}).
        success(function(data, status, headers, config) {
          if(data === "success"){
            if(templateName == "all"){
              self.templateList.splice(1, self.templateList.length - 1);
              $scope.templateListTableParams.reload();
              $scope.kalinsAlertManager.addAlert("Templates deleted successfully", "success");
            }else{
              indexToDelete = self.getTemplateIndexByName(templateName);              
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
            $scope.kalinsAlertManager.addAlert(data, "danger");
          }
        }).
        error(function(data, status, headers, config) {
          $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
        });
    }
  }

  self.loadTemplate = function(oTemplate){
    if(typeof(oTemplate) === "string"){
      //get our actual template if we just passed in the template name
      var templateIndex = self.getTemplateIndexByName(oTemplate);
      if(templateIndex >= 0){
        oTemplate = self.templateList[templateIndex];
      }else{//if the template doesn't exist, load default values at 0 index
        oTemplate = self.templateList[0];
      }
    }

    //make a copy of oTemplate so that our two instances of HTML databinding of templateName don't conflict with each other
    self.oOptions = JSON.parse($filter('json')(oTemplate));
    
    self.validateBuildPostList(self.oOptions.buildPostList);//check for missing pages and posts and make corrections accordingly

    self.buildPostListByID = [];
    var l = oTemplate.buildPostList.length;
    //loop to rebuild our buildPostListByID, which tells the page/post ng-table which items to highlight
    for(var i = 0; i<l; i++){
      if(!self.buildPostListByID[oTemplate.buildPostList[i].ID]){
        self.buildPostListByID[oTemplate.buildPostList[i].ID] = 1;
      }else{
        self.buildPostListByID[oTemplate.buildPostList[i].ID]++;
      }
    }
  }

  //checks our buildPostList to make sure all the pages and posts still exist. If any are found that don't exist, 
  //they are removed and a warning is thrown at the user, but this doesn't actually break anything.
  self.validateBuildPostList = function(list){
    var sWarning = "The following pages or posts have been deleted from your website: ",//warning message to show to user
      l = self.postList.length, //inner loop max
      nWarningLength = sWarning.length; //current length of string. We check this later. If it hasn't changed, then nothing has been deleted so we don't need to show it to the user.

    //loop backwards since we might be deleting items from the list array
    for(var i = list.length - 1; i >= 0; i--){      
      var bFound = false;
      for(var j = 0; j<l; j++){
        if(list[i].ID === self.postList[j].ID){
          bFound = true;//if we find the post in the list, then it's all good
          break;
        }
      }

      if(!bFound){
        //add the title of the missing post to the warning
        sWarning += list[i].title + ", ";
        //remove the list item from the array that was passed in
        list.splice(i, 1);
      }
    }

    //if the string has gotten bigger, it means we actually need to show it to the user
    if(sWarning.length > nWarningLength){
      sWarning = sWarning.replace(/, +$/, "");//use regex to trim off the trailing comma and space
      sWarning += ". These have also been removed from your template. This change will not be saved until you save the template.";
      $scope.kalinsAlertManager.addAlert(sWarning, "warning");
    }    
  }

  //add all the posts that are currently showing in our page/post table
  self.addAllPosts = function(){
    var l = $scope.postListTableParams.data.length;

    for(var i = 0; i < l; i++){      
      //add the item directly from the postListTableParams.data array, which contains only the posts currently displayed in the table grid
      var newObj = JSON.parse($filter('json')( $scope.postListTableParams.data[i] ));
      self.oOptions.buildPostList.push(newObj);

      //add count to the tracking array to support adding same post multiple times
      if(!self.buildPostListByID[newObj.ID]){
        self.buildPostListByID[newObj.ID] = 1;
      }else{
        self.buildPostListByID[newObj.ID]++;
      }
    }
  }

  //remove all posts from our current document
  self.removeAllPosts = function(){
    self.buildPostListByID = [];
    self.oOptions.buildPostList = [];
  }

  //add a single post to our current document
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
        self.oOptions.buildPostList.push(newObj);
        
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
    for(var i = 0; i<self.oOptions.buildPostList.length; i++){
      if(self.oOptions.buildPostList[i].ID === postID){
        self.oOptions.buildPostList.splice(i,1);
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
      
      var data = {
        filename: filename, 
      }
  
      $http({method:"POST", url:ajaxurl, params: {action:'kalins_pdf_tool_delete', _ajax_nonce:deleteNonce },  data:data}).
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
            $scope.kalinsAlertManager.addAlert(data, "danger");
          }
        }).
        error(function(data, status, headers, config) {
          $scope.kalinsAlertManager.addAlert("An error occurred: " + data, "danger");
        });
    }
  }
  
  //load our current options
  self.loadTemplate(self.sCurTemplate);
}]);  
</script>

  <div ng-app="kalinsPDFToolPage" ng-controller="InputController as InputCtrl" class="kContainer">  
    <h2>PDF Creation Station</h2>
    <h3>by Kalin Ringkvist - <a href="http://kalinbooks.com">kalinbooks.com</a></h3>
    <p>Create custom PDF files for any combination of posts and pages.</p>
    <p><a href="http://kalinbooks.com/pdf-creation-station/">Plugin page</a> | <a href="http://kalinbooks.com/pdf-creation-station/known-bugs/">Report bug</a></p>
    
    <p><a href="#" ng-click="showVideo = !showVideo">Watch a tutorial video</a></p>
    <div class="text-center" ng-show="showVideo">
      <hr>
      <iframe width="420" height="315" src="//www.youtube.com/embed/cPaz3X4RXbQ" frameborder="0" allowfullscreen></iframe>
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
        <div class="form-group text-right">
          <button ng-click="InputCtrl.addAllPosts();" class="btn btn-success">Add all</button>
        </div>
        <table ng-table="postListTableParams" show-filter="InputCtrl.postList.length > 1" class="table">
          <tr ng-repeat="post in $data" ng-class="{'active': InputCtrl.buildPostListByID[post.ID]>0}">
            <td class="k-small-width" data-title="'ID'" sortable="'ID'" filter="{ 'ID': 'text' }" ng-show="oPostToggles.aBooleans[0]">
              {{post.ID}}
            </td>
            <td data-title="'Title'" sortable="'title'" filter="{ 'title': 'text' }">
              {{post.title}}
            </td>
            <td class="k-med-width" data-title="'Date'" sortable="'date'" filter="{ 'date': 'text' }" ng-show="oPostToggles.aBooleans[1]">
              {{post.date}}
            </td>
            <td class="k-small-width" data-title="'Type'" sortable="'type'" filter="{ 'type': 'text' }" ng-show="oPostToggles.aBooleans[2]">
              {{post.type}}
            </td>
            <td class="k-small-width" data-title="'Status'" sortable="'status'" filter="{ 'status': 'text' }" ng-show="oPostToggles.aBooleans[3]">
              {{post.status}}
            </td>
            <td data-title="'Categories'" sortable="'cats'" filter="{ 'cats': 'text' }" ng-show="oPostToggles.aBooleans[4]">
              {{post.cats}}
            </td>
            <td data-title="'Tags'" sortable="'tags'" filter="{ 'tags': 'text' }" ng-show="oPostToggles.aBooleans[5]">
              {{post.tags}}
            </td>
            <td data-title="'Author'" sortable="'author'" filter="{ 'author': 'text' }" ng-show="oPostToggles.aBooleans[6]">
              {{post.author}}
            </td>
            <td class="k-small-width" data-title="'Menu Order'" sortable="'menu_order'" filter="{ 'menu_order': 'text' }" ng-show="oPostToggles.aBooleans[7]">
              {{post.menu_order}}
            </td>
            <td data-title="'Add'" ng-click="InputCtrl.addPost(post.ID);">
              <button class="btn btn-success btn-xs">Add</button>
            </td>
          </tr>
        </table>
        
        <p class="alert alert-info">
          <b>Display: </b>&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[0]"></input> ID </label>
          &nbsp;|&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[1]"></input> Date </label>
          &nbsp;|&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[2]"></input> Type </label>
          &nbsp;|&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[3]"></input> Status </label>
          &nbsp;|&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[4]"></input> Categories </label>
          &nbsp;|&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[5]"></input> Tags </label>
          &nbsp;|&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[6]"></input> Author </label>
          &nbsp;|&nbsp;
          <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="oPostToggles.aBooleans[7]"></input> Menu Order </label>
          &nbsp;|&nbsp;
          <a href="" ng-click="oPostToggles.toggleAll();">{{oPostToggles.sToggleAll}}</a>
        </p>
      </accordion-group>
      
      <accordion-group is-open="kalinsToggles.aBooleans[1]">
        <accordion-heading>
          <div><strong>My document</strong><k-help str="{{oHelpStrings['h_myDocument']}}"></k-help><i class="pull-right glyphicon" ng-class="{'glyphicon-chevron-down': kalinsToggles.aBooleans[1], 'glyphicon-chevron-right': !kalinsToggles.aBooleans[1]}"></i></div>
        </accordion-heading>
        <p ng-show="InputCtrl.oOptions.buildPostList.length === 0">Your page list will appear here. Click an Add button above to start adding pages.</p>
        <div ng-show="InputCtrl.oOptions.buildPostList.length > 0">
          <div class="form-group text-right">
            <button ng-click="InputCtrl.removeAllPosts();" class="btn btn-danger">Remove All</button>
          </div>
          <table class="table">
            <tbody ui:sortable ng-model="InputCtrl.oOptions.buildPostList">
              <tr ng-repeat="post in InputCtrl.oOptions.buildPostList">
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
        </div>
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

          <p class="alert alert-success text-center">
            <k-help str="{{oHelpStrings['h_filenameTypes']}}"></k-help>
            <label>File name: <input type="text" class="form-control k-inline-text-input" ng-model="InputCtrl.oOptions.filename" ></input></label>
            &nbsp;
            <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.bCreatePDF"></input> .pdf </label>
            &nbsp;|&nbsp;
            <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.bCreateHTML"></input> .html </label>
            &nbsp;|&nbsp;
            <label class="k-checkbox"><input type='checkbox' class="form-control" ng-model="InputCtrl.oOptions.bCreateTXT"></input> .txt </label>
            &nbsp;
            <button ng-click="InputCtrl.createDocument();" class="btn btn-success">Create Documents!</button>
          </p>        
          
          <p class="alert alert-info text-center">
            <k-help str="{{oHelpStrings['h_saveAsTemplate']}}"></k-help>
            <label>Name: <input type="text" class="form-control k-inline-text-input" ng-model="InputCtrl.oOptions.templateName" ></input></label>
            &nbsp;
            <button ng-disabled="InputCtrl.oOptions.templateName === 'original defaults'" ng-click='InputCtrl.saveTemplate();' class="btn btn-success">Save as Template</button>
          </p>

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
        <p ng-show="InputCtrl.templateList.length === 0">Find the Save as Template button in the Create Files section above to save your current document as your first template.</p>
        <div ng-show="InputCtrl.templateList.length > 0">
          <div class="form-group" ng-show="InputCtrl.templateList.length > 1">
            <button ng-click="InputCtrl.deleteTemplate('all');" class="btn btn-danger">Delete all</button>
          </div>
          
          <table ng-table="templateListTableParams" show-filter="InputCtrl.templateList.length > 1" class="table">
            <tr ng-repeat="template in $data" ng-class="{'active': InputCtrl.oOptions.templateName === template.templateName}">
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
                 <button ng-disabled="{{template.templateName === 'original defaults'}}" ng-click="InputCtrl.deleteTemplate(template.templateName);" class="btn btn-warning btn-xs">Delete</button>
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
        <?php
          $sAbout = file_get_contents(WP_PLUGIN_DIR . '/kalins-pdf-creation-station/help/about.html');
          echo $sAbout;
        ?>
      </accordion-group>

    </accordion>
  </div>
</html>