<?php
	
	if ( !function_exists( 'add_action' ) ) {
		echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
		exit;
	}
	
	//echo get_shortcode_regex();
	
	kalinsPDF_createPDFDir();//make sure our PDF dir exists
	
	$create_nonce = wp_create_nonce( 'kalins_pdf_tool_create' );
	$delete_nonce = wp_create_nonce( 'kalins_pdf_tool_delete' );
	$reset_nonce = wp_create_nonce( 'kalins_pdf_tool_reset' );
	
	$adminOptions = kalins_pdf_get_tool_options();
	
	/*$post_types = get_post_types('','names');
	$typeString = "";
	foreach ($post_types as $post_type ) {//loop to add a meta box to each type of post (pages, posts and custom)
		if($post_type != "post" && $post_type != "attachment" && $post_type != "revision"){
			$typeString = $typeString ."," .$post_type; 
		}
	}*/
	
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
	
	$uploads = wp_upload_dir();
	//$pdfDir = $uploads['basedir'] .'/kalins-pdf/';
	$pdfDir = KALINS_PDF_DIR;
	
	$pdfURL = $uploads['baseurl'] .'/kalins-pdf/';
	
	$pdfURL = KALINS_PDF_URL;
	
	//echo $pdfURL . "url and dir" . $pdfDir;
	//$pdfDir = $pdfDirBase;
	//$pdfDir = WP_PLUGIN_DIR . '/kalins-pdf-creation-station/pdf/';
	//if ($handle = opendir(get_bloginfo('wpurl') .'/wp-content/plugins/kalins-pdf-creation-station/pdf/')) {//open pdf directory//get_bloginfo('wpurl') .'/wp-content/plugins/kalins-pdf-creation-station/pdf/'
	//echo "my pdfDir" .$pdfDir;
	
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

jQuery(document).ready(function($){
	var pdfList = <?php echo json_encode($pdfList);//hand over the objects and vars that javascript will need?>;
	var pageList = <?php echo json_encode($pageList);?>;
	var postList = <?php echo json_encode($postList); ?>;
	var customList = <?php echo json_encode($customList); ?>;
	var createNonce = '<?php echo $create_nonce; //pass a different nonce security string for each possible ajax action?>'
	var deleteNonce = '<?php echo $delete_nonce; ?>';
	var resetNonce = '<?php echo $reset_nonce; ?>';
	
	function buildFileTable(){//build the file table - we build it all in javascript so we can simply rebuild it whenever an entry is added through ajax
	
		if(pdfList.length == 0){
			$('#pdfListDiv').html("You do not have any custom PDF files.");
			return;
		}
		
		function tc(str){
			return "<td>" + str + "</td>";
		}
		
		var tableHTML = "<table width='%100' border='1' cellspacing='1' cellpadding='3'><tr><th scope='col'>#</th><th scope='col'>File Name</th><th scope='col'>Creation Date</th><th scope='col'>Delete&nbsp;&nbsp;<button name='btnDeleteAll' id='btnDeleteAll'>Delete All</button></th></tr>";
			
		var l = pdfList.length;
		for(var i=0; i<l; i++){
			var fileLink = tc("<a href='<?php echo $pdfURL; ?>" + pdfList[i].fileName + "' target='_blank'>" + pdfList[i].fileName + "</a>");
			tableHTML += "<tr>" + tc(i) + fileLink + tc(pdfList[i].date) + tc("<button name='btnDelete_" + i + "' id='btnDelete_" + i + "'>Delete</button>") + "</tr>";
		}
	
		tableHTML += "</table>";
		$('#pdfListDiv').html(tableHTML);
		
		for(i=0; i<l; i++){
			$('#btnDelete_' + i).click(function(){
				var fileIndex = parseInt($(this).attr('name').substr(10));		
				if(confirm("Are you sure you want to delete " + pdfList[fileIndex].fileName + "?")){						
					deleteFile(pdfList[fileIndex].fileName, fileIndex);
				}
			});
		}
		
		$('#btnDeleteAll').click(function(){
			if(confirm("Are you sure you want to delete all your custom created PDF files?")){
				deleteFile("all");
			}
		});
	}
	
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
	
	function deleteFile(fileName, indexToDelete){//takes a single fileName or "all"

		var data = {action: 'kalins_pdf_tool_delete', filename: fileName, _ajax_nonce : deleteNonce};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			//alert('Got this from the server: ' + response.substr(0, response.lastIndexOf("}") + 1));
			
			//alert(response);
			var newFileData = JSON.parse(response.substr(0, response.lastIndexOf("}") + 1));//parse response while removing strange trailing 0 from the response (anyone know why that 0 is being added by jquery or wordpress?)
			if(newFileData.status == "success"){
				if(fileName == "all"){
					pdfList = new Array();
					$('#createStatus').html("Files deleted successfully");
				}else{
					$('#createStatus').html("File deleted successfully");
					pdfList.splice(indexToDelete, 1);
				}
				buildFileTable();
			}else{
				//if(newFileData.status == "exists"){
				$('#createStatus').html(newFileData.status);
				//}
			}
		});
	}
	
	$('#btnCreate').click(function() {
		$('#sortDialog').dialog('close');
								   
		var sortString = $("#sortable").sortable('toArray').join(",");
		
		createDocument(sortString);
	});
	
	$('#createNow').click(function() {
		
		var sortString = '';
		var pageCount = 0;
		var l = pageList.length;		   
		for(var i=0; i<l; i++){
			if($('#chk' + pageList[i]['ID']).is(':checked')){
				//pageIDList += "," + pageList[i].ID;
				sortString += 'pg_' + pageList[i]['ID'] + ",";
				pageCount++;
			}
		}
		
		var l = customList.length;		   
		for(var i=0; i<l; i++){
			if($('#chk' + customList[i]['ID']).is(':checked')){
				sortString += 'po_' + customList[i]['ID'] + ",";
				pageCount++;
			}
		}

		var l = postList.length;		   
		for(var i=0; i<l; i++){
			if($('#chk' + postList[i]['ID']).is(':checked')){
				sortString += 'po_' + postList[i]['ID'] + ",";
				pageCount++;
			}
		}
		
		if(pageCount == 0){
			$('#createStatus').html("Error: you must select at least one page or post to create a PDF.");
			return;
		}
		
		sortString = sortString.substr(0, sortString.length - 1);
		createDocument(sortString);
	});
	
	function createDocument(sortString){
		
		var data = { action: 'kalins_pdf_tool_create',
			pageIDs : sortString,
			_ajax_nonce : createNonce
		}

		data.titlePage = $("#txtTitlePage").val();
		data.beforePage = $("#txtBeforePage").val();
		data.beforePost = $("#txtBeforePost").val();
		data.afterPage = $("#txtAfterPage").val();
		data.afterPost = $("#txtAfterPost").val();
		data.fileNameCont = $("#txtFileName").val();
		data.includeImages = $("#chkIncludeImages").is(':checked');
		data.runShortcodes = $("#chkRunShortcodes").is(':checked');
		data.runFilters = $("#chkRunFilters").is(':checked');
		data.convertYoutube = $("#chkConvertYoutube").is(':checked');
		data.convertVimeo = $("#chkConvertVimeo").is(':checked');
		data.convertTed = $("#chkConvertTed").is(':checked');
		//data.includeTables = $("#chkIncludeTables").is(':checked');
		data.headerTitle = $("#txtHeaderTitle").val();
		data.headerSub = $("#txtHeaderSub").val();
		data.finalPage = $("#txtFinalPage").val();
		data.fontSize = $("#txtFontSize").val();
		data.autoPageBreak = $("#chkAutoPageBreak").is(':checked');
		data.includeTOC = $("#chkIncludeTOC").is(':checked');
		
		$('#createStatus').html("Building PDF file. Wait time will depend on the length of the document, image complexity and current server load. Refreshing the page or navigating away will cancel the build.");

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			
			var startPosition = response.indexOf("{")
			var responseObjString = response.substr(startPosition, response.lastIndexOf("}") - startPosition + 1);
			
			var newFileData = JSON.parse(responseObjString);
			if(newFileData.status == "success"){
				$('#createStatus').html("File created successfully");
				pdfList.push(newFileData);
				buildFileTable();
			}else{
				$('#createStatus').html("Error: " + newFileData.status);
			}
		});
	}
	
	$('#btnReset').click(function(){
		if(confirm("Are you sure you want to reset all of your field values? You will lose all the information you have entered into the form. (This will NOT delete or change your existing PDF documents.)")){
			var data = { action: 'kalins_pdf_tool_defaults', _ajax_nonce : resetNonce};
			
			jQuery.post(ajaxurl, data, function(response) {
				var newValues = JSON.parse(response.substr(0, response.lastIndexOf("}") + 1));
				$('#txtBeforePage').val(newValues["beforePage"]);
				$('#txtBeforePost').val(newValues["beforePost"]);
				$('#txtAfterPage').val(newValues["afterPage"]);
				$('#txtAfterPost').val(newValues["afterPost"]);
				$('#txtTitlePage').val(newValues["titlePage"]);
				$('#txtFinalPage').val(newValues["finalPage"]);
				$('#txtFontSize').val(newValues["fontSize"]);
				$('#txtHeaderTitle').val(newValues["headerTitle"]);
				$('#txtHeaderSub').val(newValues["headerSub"]);
				$('#txtFileName').val(newValues["filename"]);
				
				if(newValues["includeImages"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkIncludeImages').attr('checked', true);
				}else{
					$('#chkIncludeImages').attr('checked', false);
				}
				
				if(newValues["runShortcodes"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkRunShortcodes').attr('checked', true);
				}else{
					$('#chkRunShortcodes').attr('checked', false);
				}
				
				if(newValues["runFilters"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkRunFilters').attr('checked', true);
				}else{
					$('#chkRunFilters').attr('checked', false);
				}
				
				if(newValues["convertYoutube"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkConvertYoutube').attr('checked', true);
				}else{
					$('#chkConvertYoutube').attr('checked', false);
				}
				
				if(newValues["convertVimeo"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkConvertVimeo').attr('checked', true);
				}else{
					$('#chkConvertVimeo').attr('checked', false);
				}
				
				if(newValues["convertTed"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkConvertTed').attr('checked', true);
				}else{
					$('#chkConvertTed').attr('checked', false);
				}
				
				if(newValues["autoPageBreak"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkAutoPageBreak').attr('checked', true);
				}else{
					$('#chkAutoPageBreak').attr('checked', false);
				}
				
				if(newValues["includeTOC"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkIncludeTOC').attr('checked', true);
				}else{
					$('#chkIncludeTOC').attr('checked', false);
				}
				
			});
		}
	});
	
	function toggleWidgets() {//make menus collapsible
		$('.collapse').addClass('plus');

		$('.collapse').click(function() {
			$(this).toggleClass('plus').toggleClass('minus').next().toggle(180);
		});
	}
	
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
			
			
			//$('#sortHolder').html("Hello this is my HTML Crap-------------------------------------------------------");
			
			$(function() {//set the div as sortable every time we open the dialog (doing this earlier and just calling refresh didn't work)
				$("#sortable").sortable();
				$("#sortable").disableSelection();
			});
		
			$('#sortDialog').dialog('open');
			return false;
		});
	});

	buildFileTable();
	toggleWidgets();
});
	
</script>


<!--<style>

.ui-widget input, .ui-widget select {font-family: arial; padding: 0px 0px;}
.ui-dialog-content {font-weight: normal; line-height: normal; font-family: arial; font-size: 8pt; }
.ui-widget-content {font-weight: normal; line-height: normal; font-family: arial; font-size: 8pt;}

</style> -->

<h2>PDF Creation Station</h2>

<h3>by Kalin Ringkvist - kalinbooks.com</h3>

<p>Create custom PDF files for any combination of posts and pages.</p> 

<div class='collapse'><b>Select Pages and Posts</b></div>
<div class="wideHolder">
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
				
				//echo "-----" .$parent ."----";
                
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

<div class='collapse'><b>Insert HTML before every page or post</b></div>
   <div class="txtfieldHolder">
        <div class="textAreaDiv">
            <b>HTML to insert before every page:</b><br />
            <textarea class="txtArea" name='txtBeforePage' id='txtBeforePage' rows='8'><?php echo $adminOptions["beforePage"]; ?></textarea>
        </div>
        <div class="textAreaDiv">
            <b>HTML to insert before every post:</b><br />
            <textarea class="txtArea" name='txtBeforePost' id='txtBeforePost' rows='8'><?php echo $adminOptions["beforePost"]; ?></textarea>
        </div>
    </div>
    <div class='collapse'><b>Insert HTML after every page or post</b></div>
    <div class="txtfieldHolder">
        <div class="textAreaDiv">
            <b>HTML to insert after every page:</b><br />
            <textarea class="txtArea" name='txtAfterPage' id='txtAfterPage' rows='8'><?php echo $adminOptions["afterPage"]; ?></textarea>
        </div>
        <div class="textAreaDiv">
            <b>HTML to insert after every post:</b><br />
            <textarea class="txtArea" name='txtAfterPost' id='txtAfterPost' rows='8'><?php echo $adminOptions["afterPost"]; ?></textarea>
        </div>
    </div>
    <div class='collapse'><b>Insert HTML for title and final pages</b></div>
    <div class="txtfieldHolder">
        <div class="textAreaDiv">
            <b>HTML to insert for title page:</b><br />
            <textarea class="txtArea" name='txtTitlePage' id='txtTitlePage' rows='8'><?php echo $adminOptions["titlePage"]; ?></textarea>
        </div>
        <div class="textAreaDiv">
            <b>HTML to insert for final page:</b><br />
            <textarea class="txtArea" name='txtFinalPage' id='txtFinalPage' rows='8' ><?php echo $adminOptions["finalPage"]; ?></textarea>
        </div>
    </div>
    <div class='collapse'><b>CREATE PDF!</b></div>
    <div class="generalHolder">
        <p>Header title: <input type='text' name='txtHeaderTitle' id='txtHeaderTitle' class='txtHeader' value='<?php echo $adminOptions["headerTitle"]; ?>'></input></p>
        <p>Header sub title: <input type='text' name='txtHeaderSub' id='txtHeaderSub' class='txtHeader' value='<?php echo $adminOptions["headerSub"]; ?>'></input></p><br/>
        
         <p><input type='checkbox' id='chkIncludeImages' name='chkIncludeImages' <?php if($adminOptions["includeImages"] == "true"){echo "checked='yes' ";} ?>></input> Include Images &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type="text" id="txtFontSize" size="2" maxlength="3" value='<?php echo $adminOptions["fontSize"]; ?>' /> Content font size &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type='checkbox' id='chkRunShortcodes' name='chkRunShortcodes' <?php if($adminOptions["runShortcodes"] == "true"){echo "checked='yes' ";} ?>></input> Run other plugin shortcodes, &nbsp;<input type='checkbox' id='chkRunFilters' name='chkRunFilters' <?php if($adminOptions["runFilters"] == "true"){echo "checked='yes' ";} ?>></input> and content filters</p>
         
         <p>Convert videos to links: &nbsp;&nbsp;<input type='checkbox' id='chkConvertYoutube' name='chkConvertYoutube' <?php if($adminOptions["convertYoutube"] == "true"){echo "checked='yes' ";} ?>></input> YouTube, &nbsp;<input type='checkbox' id='chkConvertVimeo' name='chkConvertVimeo' <?php if($adminOptions["convertVimeo"] == "true"){echo "checked='yes' ";} ?>></input> Vimeo, &nbsp;<input type='checkbox' id='chkConvertTed' name='chkConvertTed' <?php if($adminOptions["convertTed"] == "true"){echo "checked='yes' ";} ?>></input> Ted Talks</p>
         
         <br/>
        
        File name: <input type="text" name='txtFileName' id='txtFileName' value='<?php echo $adminOptions["filename"]; ?>' ></input>.pdf  &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp; <input type='checkbox' id='chkAutoPageBreak' name='chkAutoPageBreak' <?php if($adminOptions["autoPageBreak"] == "true"){echo "checked='yes' ";} ?>></input> Automatic page breaks &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp; <input type='checkbox' id='chkIncludeTOC' name='chkIncludeTOC' <?php if($adminOptions["includeTOC"] == "true"){echo "checked='yes' ";} ?>></input> Include Table of Contents
        </p>
        <p align="center"><br />
        <button id="btnOpenDialog">Create PDF!</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button type='button' id='btnReset'>Reset Defaults</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a name="createNow" id="createNow" href="javascript:void(0);" title="Use this if the 'Create PDF!' button won't properly show the popup. You won't be able to re-order your pages, but at least you can create a document.">create now!</a></p>
        <p align="center"><span id="createStatus">&nbsp;</span></p>
        
    </div>
    <div class='collapse'><b>Existing PDF Files</b></div>
    <div class="generalHolder" id="pdfListDiv"><p>List of compiled documents goes here</p></div>
    
    <div class='collapse'><b>Shortcodes</b></div>
    <div class="generalHolder">
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
    <div class='collapse'><b>About</b></div>
    <div class="generalHolder">
    
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
    
    <div id="sortDialog" title="Adjust Order and Create"><div id="sortHolder" class="sortHolder"></div><p align="center"><br /><button id="btnCreateCancel">Cancel</button>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<button id="btnCreate">Create PDF!</button></p></div>
</html>