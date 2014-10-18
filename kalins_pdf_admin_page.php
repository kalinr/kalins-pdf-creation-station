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
	
	$('#btnReset').click(function(){
		if(confirm("Are you sure you want to reset all of your field values? You will lose all the information you have entered into the form and your cache of PDF files will be cleared.")){
			var data = { action: 'kalins_pdf_reset_admin_defaults', _ajax_nonce : resetNonce};
			
			jQuery.post(ajaxurl, data, function(response) {
				
				var newValues = JSON.parse(response.substr(0, response.lastIndexOf("}") + 1));
				
				$('#txtBeforePage').val(newValues["beforePage"]);
				$('#txtBeforePost').val(newValues["beforePost"]);
				$('#txtAfterPage').val(newValues["afterPage"]);
				$('#txtAfterPost').val(newValues["afterPost"]);
				$('#txtTitlePage').val(newValues["titlePage"]);
				$('#txtFinalPage').val(newValues["finalPage"]);
				
				$('#txtHeaderTitle').val(newValues["headerTitle"]);
				$('#txtHeaderSub').val(newValues["headerSub"]);
				
				$('#txtLinkText').val(newValues["linkText"]);
				$('#txtBeforeLink').val(newValues["beforeLink"]);
				$('#txtAfterLink').val(newValues["afterLink"]);
				
				$('#txtFontSize').val(newValues["fontSize"]);
				$('#txtFilename').val(newValues["filename"]);
				$('#txtWordCount').val(newValues["wordCount"]);
				
				if(newValues["includeImages"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkIncludeImages').attr('checked', true);
				}else{
					$('#chkIncludeImages').attr('checked', false);
				}
				
				$("input[name='kalinsPDFLink']").val(newValues["showLink"]);//set link radio button option
				$("#opt_" + newValues["showLink"]).attr("checked", "checked"); 
				
				if(newValues["filenameByTitle"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkFilenameByTitle').attr('checked', true);
				}else{
					$('#chkFilenameByTitle').attr('checked', false);
				}
				
				if(newValues["showOnMulti"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkShowOnMulti').attr('checked', true);
				}else{
					$('#chkShowOnMulti').attr('checked', false);
				}
				
				if(newValues["doCleanup"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkDoCleanup').attr('checked', true);
				}else{
					$('#chkDoCleanup').attr('checked', false);
				}
				
				if(newValues["autoGenerate"] == 'true'){//hmmm, maybe there's a way to get an actual boolean to be passed through instead of the string
					$('#chkAutoGenerate').attr('checked', true);
				}else{
					$('#chkAutoGenerate').attr('checked', false);
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
				
			});
		}
	});
	
	$('#btnSave').click(function(){
		
		var data = { action: 'kalins_pdf_admin_save',
			_ajax_nonce : saveNonce
		}

		data.beforePage = $("#txtBeforePage").val();
		data.beforePost = $("#txtBeforePost").val();
		data.afterPage = $("#txtAfterPage").val();
		data.afterPost = $("#txtAfterPost").val();
		data.titlePage = $("#txtTitlePage").val();
		data.finalPage = $("#txtFinalPage").val();
		data.headerTitle = $("#txtHeaderTitle").val();
		data.headerSub = $("#txtHeaderSub").val();
		data.linkText = $("#txtLinkText").val();
		data.beforeLink = $("#txtBeforeLink").val();
		data.afterLink = $("#txtAfterLink").val();
		data.fontSize = $("#txtFontSize").val();
		data.includeImages = $("#chkIncludeImages").is(':checked');
		data.runShortcodes = $("#chkRunShortcodes").is(':checked');
		data.runFilters = $("#chkRunFilters").is(':checked');
		data.convertYoutube = $("#chkConvertYoutube").is(':checked');
		data.convertVimeo = $("#chkConvertVimeo").is(':checked');
		data.convertTed = $("#chkConvertTed").is(':checked');
		//data.includeTables = $("#chkIncludeTables").is(':checked');
		data.showLink = $("input[name='kalinsPDFLink']:checked").val();
		data.wordCount = $("#txtWordCount").val();
		data.showOnMulti = $("#chkShowOnMulti").is(':checked');
		data.filenameByTitle = $("#chkFilenameByTitle").is(':checked');//chkAutoGenerate
		
		data.autoGenerate = $("#chkAutoGenerate").is(':checked');
		
		data.doCleanup =  $("#chkDoCleanup").is(':checked');
		
		//alert(data.showLink + "showLink");
		
		$('#createStatus').html("Saving settings...");

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			var startPosition = response.indexOf("{");
			var responseObjString = response.substr(startPosition, response.lastIndexOf("}") - startPosition + 1);
			
			var newFileData = JSON.parse(responseObjString);
			if(newFileData.status == "success"){
				$('#createStatus').html("Settings saved successfully.");
			}else{
				$('#createStatus').html(response);
			}
		});
	});
	
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
	
	function toggleWidgets() {//make menus collapsible
		$('.collapse').addClass('plus');

		$('.collapse').click(function() {
			$(this).toggleClass('plus').toggleClass('minus').next().toggle(180);
		});
	}
	
	toggleWidgets();
});
	
</script>


<h2>PDF Creation Station</h2>

<h3>by Kalin Ringkvist - <a href="http://kalinbooks.com/">kalinbooks.com</a></h3>

<p>Settings for creating PDF files on individual pages and posts. For more information, click the help button to the right.</p>


<div class='collapse'><b>Insert HTML before page or post</b></div>
   <div class="txtfieldHolder">
        <div class="textAreaDiv">
            <b>HTML to insert before page:</b><br />
            <textarea class="txtArea" name='txtBeforePage' id='txtBeforePage' rows='8'><?php echo $adminOptions["beforePage"]; ?></textarea>
        </div>
        <div class="textAreaDiv">
            <b>HTML to insert before post:</b><br />
            <textarea class="txtArea" name='txtBeforePost' id='txtBeforePost' rows='8'><?php echo $adminOptions["beforePost"]; ?></textarea>
        </div>
    </div>
    <div class='collapse'><b>Insert HTML after page or post</b></div>
    <div class="txtfieldHolder">
        <div class="textAreaDiv">
            <b>HTML to insert after page:</b><br />
            <textarea class="txtArea" name='txtAfterPage' id='txtAfterPage' rows='8'><?php echo $adminOptions["afterPage"]; ?></textarea>
        </div>
        <div class="textAreaDiv">
            <b>HTML to insert after post:</b><br />
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
    <div class='collapse'><b>Options</b></div>
    <div class="generalHolder">
        <p>Header title: <input type='text' name='txtHeaderTitle' id='txtHeaderTitle' class='txtHeader' value='<?php echo $adminOptions["headerTitle"]; ?>'></input></p>
        <p>Header sub title: <input type='text' name='txtHeaderSub' id='txtHeaderSub' class='txtHeader' value='<?php echo $adminOptions["headerSub"]; ?>'></input></p>
        <br/>
        <p>Link text: <input type="text" id='txtLinkText' class='txtHeader' value='<?php echo $adminOptions["linkText"]; ?>' /></p>
        <p>Before Link: <input type="text" id='txtBeforeLink' class='txtHeader' value='<?php echo $adminOptions["beforeLink"]; ?>' /></p>
        <p>After Link: <input type="text" id='txtAfterLink' class='txtHeader' value='<?php echo $adminOptions["afterLink"]; ?>' /></p>
        <br/>
        <p><input type='checkbox' id='chkIncludeImages' name='chkIncludeImages' <?php if($adminOptions["includeImages"] == "true"){echo "checked='yes' ";} ?>></input> Include Images &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type="text" id="txtFontSize" size="2" maxlength="3" value='<?php echo $adminOptions["fontSize"]; ?>' /> Content font size &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type='checkbox' id='chkRunShortcodes' name='chkRunShortcodes' <?php if($adminOptions["runShortcodes"] == "true"){echo "checked='yes' ";} ?>></input> Run other plugin shortcodes, &nbsp;<input type='checkbox' id='chkRunFilters' name='chkRunFilters' <?php if($adminOptions["runFilters"] == "true"){echo "checked='yes' ";} ?>></input> and content filters</p>
        <p>Convert videos to links: &nbsp;&nbsp;<input type='checkbox' id='chkConvertYoutube' name='chkConvertYoutube' <?php if($adminOptions["convertYoutube"] == "true"){echo "checked='yes' ";} ?>></input> YouTube, &nbsp;<input type='checkbox' id='chkConvertVimeo' name='chkConvertVimeo' <?php if($adminOptions["convertVimeo"] == "true"){echo "checked='yes' ";} ?>></input> Vimeo, &nbsp;<input type='checkbox' id='chkConvertTed' name='chkConvertTed' <?php if($adminOptions["convertTed"] == "true"){echo "checked='yes' ";} ?>></input> Ted Talks</p>
        <br/>
        
        <p>Default Link Placement (can be overwritten in page/post edit page):</p>
        
        <?php
		//KLUDGE I should probably replace this with some jquery that runs on page load to set the proper value of the option button rather than running through this switch statement just to check an option button
		switch($adminOptions["showLink"]){
			case "top":
				echo '<p><input type="radio" name="kalinsPDFLink" value="top" id="opt_top" checked /> Link at top of page<br /><input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" /> Link at bottom of page<br /><input type="radio" name="kalinsPDFLink" value="none" id="opt_none" /> Do not generate PDF</p>';
				break;
			case "bottom":
				echo '<p><input type="radio" name="kalinsPDFLink" value="top" id="opt_top" /> Link at top of page<br /><input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" checked /> Link at bottom of page<br /><input type="radio" name="kalinsPDFLink" value="none" id="opt_none" /> Do not generate PDF</p>';
				break;
        	case "none":
				echo '<p><input type="radio" name="kalinsPDFLink" value="top" id="opt_top" /> Link at top of page<br /><input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" /> Link at bottom of page<br /><input type="radio" name="kalinsPDFLink" value="none" id="opt_none" checked /> Do not generate PDF</p>';
				break;
		}
		?>
        <p>
        <input type="text" id="txtWordCount" size="3" maxlength="5" value='<?php echo $adminOptions["wordCount"]; ?>' /> Minimum post word count
        </p><br/>
        
        <p><input type='checkbox' id='chkFilenameByTitle' name='chkFilenameByTitle' <?php if($adminOptions["filenameByTitle"] == "true"){echo "checked='yes' ";} ?>></input> Use post slug for PDF filename instead of ID</p>
        
        <p><input type='checkbox' id='chkShowOnMulti' name='chkShowOnMulti' <?php if($adminOptions["showOnMulti"] == "true"){echo "checked='yes' ";} ?>></input> Show on home, category, tag and search pages (does not work if you use excerpts on any of these pages)</p>
        
        <p><input type='checkbox' id='chkAutoGenerate' name='chkAutoGenerate' <?php if($adminOptions["autoGenerate"] == "true"){echo "checked='yes' ";} ?>></input> Automatically generate PDFs on publish and update</p><br/>
        
        <p><!--&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<input type='checkbox' id='chkIncludeTables' name='chkIncludeTables' if($adminOptions["includeTables"] == 'true'){echo "checked='yes' ";} ></input> Include Tables --></p>
        
</p>
<p align="center"><br />
        <button id="btnSave">Save Settings</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button type='button' id='btnReset'>Reset Defaults</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button type='button' id='btnCreateAll'>Create All</button></p>
        <p align="center"><span id="createStatus">&nbsp;</span></p>
    </div>
    
    <div class='collapse'><b>Shortcodes</b></div>
    <div class="generalHolder">
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
        
        
        <p><input type='checkbox' id='chkDoCleanup' name='chkDoCleanup' <?php if($adminOptions["doCleanup"] == "true"){echo "checked='yes' ";} ?>></input> Upon plugin deactivation clean up all database entries</p>
        
        
        
         <p>You may also like <a href="http://kalinbooks.com/easy-edit-links-wordpress-plugin/" target="_blank">Kalin's Easy Edit Links</a> - <br /> Adds a box to your page/post edit screen with links and edit buttons for all pages, posts, tags, categories, and links for convenient edit-switching and internal linking.</p>
         
         <p>Or <a href="http://kalinbooks.com/post-list-wordpress-plugin/" target="_blank">Kalin's Post List</a> - <br /> Use a shortcode in your posts to insert dynamic, highly customizable lists of posts, pages, images, or attachments based on categories and tags. Works for table-of-contents pages or as a related posts plugin.</p>
       
         
         
    </div>
</html>