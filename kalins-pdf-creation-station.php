<?php
/*
Plugin Name: Kalin's PDF Creation Station
Version: 3.2
Plugin URI: http://kalinbooks.com/pdf-creation-station/
Description: Build highly customizable PDF documents from any combination of pages and posts, or add a link to any page to download a PDF of that post.
Author: Kalin Ringkvist
Author URI: http://kalinbooks.com/

Kalin's PDF Creation station by Kalin Ringkvist (email: kalin@kalinflash.com)

Thanks to Marcos Rezende's Blog as PDF and Aleksander Stacherski's AS-PDF plugins which provided a great starting point.

License:
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*

found this in a readme.txt file. Can I embed video into my readme? Exactly what I'm gonna try when I make my demo video
Below is a slightly outdated example video showing Custom Post Type UI in action!
[vimeo http://vimeo.com/10187055]

look into: // set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 045');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

use ProgressBar.class.php in a new php file in an iframe on tool page for pdf generation

*/

if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

define("KALINS_PDF_ADMIN_OPTIONS_NAME", "kalins_pdf_admin_options");
define("KALINS_PDF_TOOL_OPTIONS_NAME", "kalins_pdf_tool_options");

//-------edit these lines to change the location of your generated PDF documents----------

if(!defined("KALINS_PDF_DIR")){//only set directories and URLs if they are not already set in wp-config.php
	$uploads = wp_upload_dir();

	define("KALINS_PDF_DIR", $uploads['basedir'] .'/kalins-pdf/');//base location of PDF files
	define("KALINS_PDF_SINGLES_DIR", KALINS_PDF_DIR .'singles/');//location to save individual page/post PDF files

	define("KALINS_PDF_URL", $uploads['baseurl'] .'/kalins-pdf/');//the url string will be different from the directory so be careful
	define("KALINS_PDF_SINGLES_URL", KALINS_PDF_URL .'singles/');
}

//To permanently change these values so they don't get overwritten when I upgrade this plugin, copy them to your wp-config.php. However, you won't have access to the wp_upload_dir() function so you will need to hard-code the paths
//---------------------------------


//---------------------------------
//to change the PDF location to your document root, comment out the if statement above, then un-comment the if statement below:
//(This code will function as-is in wp-config.php)

/*
if(!defined("KALINS_PDF_DIR")){//only set directories and URLs if they are not already set in wp-config.php
	define("KALINS_PDF_DIR", $_SERVER['DOCUMENT_ROOT'] .'/pdf/');//base location of PDF files
	define("KALINS_PDF_SINGLES_DIR", KALINS_PDF_DIR);//location to save individual page/post PDF files

	define("KALINS_PDF_URL", "http://" .$_SERVER['HTTP_HOST'] .'/pdf/');
	define("KALINS_PDF_SINGLES_URL", KALINS_PDF_URL);//this will put all PDFs in the same folder
}
*/
//---------------------------------

function kalins_pdf_admin_page() {//load php that builds our admin page
	require_once( WP_PLUGIN_DIR . '/kalins-pdf-creation-station/kalins_pdf_admin_page.php');
}

function kalins_pdf_tool_page() {//load php that builds our tool page
	require_once( WP_PLUGIN_DIR . '/kalins-pdf-creation-station/kalins_pdf_tool_page.php');
}

function kalins_pdf_admin_init(){
	
	//creation tool ajax connections
	add_action('wp_ajax_kalins_pdf_tool_create', 'kalins_pdf_tool_create');
	add_action('wp_ajax_kalins_pdf_tool_delete', 'kalins_pdf_tool_delete');
	add_action('wp_ajax_kalins_pdf_tool_defaults', 'kalins_pdf_tool_defaults');
	
	//single page admin ajax connections
	add_action('wp_ajax_kalins_pdf_reset_admin_defaults', 'kalins_pdf_reset_admin_defaults');//kalins_pdf_admin_save
	add_action('wp_ajax_kalins_pdf_admin_save', 'kalins_pdf_admin_save');
	add_action('wp_ajax_kalins_pdf_create_all', 'kalins_pdf_create_all');
	
	//add_action('contextual_help', 'kalins_pdf_contextual_help', 10, 2);
	
	register_deactivation_hook( __FILE__, 'kalins_pdf_cleanup' );
	
	wp_register_style('kalinPDFStyle', WP_PLUGIN_URL . '/kalins-pdf-creation-station/kalins_pdf_styles.css');

	//--------------you may comment-out the foreach loop if you are using hard-coded PDF links in your theme. This will make your admin panels run slightly more efficiently.-------------
	
	$post_types = get_post_types('','names'); 
	foreach ($post_types as $post_type ) {//loop to add a meta box to each type of post (pages, posts and custom)
		if($post_type != "attachment" && $post_type != "revision" && $post_type != "nav_menu_item"){//don't show metabox for these three post types, if they even have normal edit screens
			add_meta_box( 'kalinsPDF_sectionid', __( "PDF Creation Station", 'kalinsPDF_textdomain' ), 'kalinsPDF_inner_custom_box', $post_type, 'side' );
		}
	}
	//--------------------------------
}

function kalins_pdf_configure_pages() {
	
	global $kPDFadminPage;
	
	$kPDFadminPage = add_submenu_page('options-general.php', 'Kalins PDF Creation Station', 'PDF Creation Station', 'manage_options', 'kalins-pdf-admin', 'kalins_pdf_admin_page');
	
	global $kPDFtoolPage;
	
	$kPDFtoolPage = add_submenu_page('tools.php', 'Kalins PDF Creation Station', 'PDF Creation Station', 'manage_options', 'kalins-pdf-tool', 'kalins_pdf_tool_page');
	
	add_action( "admin_print_scripts-$kPDFadminPage", 'kalins_pdf_admin_head' );
	add_action('admin_print_styles-' . $kPDFadminPage, 'kalins_pdf_admin_styles');
	
	add_action( "admin_print_scripts-$kPDFtoolPage", 'kalins_pdf_admin_head' );
	add_action('admin_print_styles-' . $kPDFtoolPage, 'kalins_pdf_admin_styles');
	
	add_filter('contextual_help', 'kalins_pdf_contextual_help', 10, 3);
}

function kalins_pdf_admin_head() {
	
	//echo "My plugin admin head";
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui-sortable");
	wp_enqueue_script("jquery-ui-dialog");
}

function kalins_pdf_admin_styles(){//not sure why this didn't work if called from pdf_admin_head
	wp_enqueue_style('kalinPDFStyle');
}

function kalinsPDF_inner_custom_box($post) {//creates the box that goes on the post/page edit page
  	// show nonce for verification and post box label
  	echo '<input type="hidden" name="kalinsPDF_noncename" id="kalinsPDF_noncename" value="' .wp_create_nonce( plugin_basename(__FILE__) ) . '" />Create PDF of this page? <br />';
	
	$meta = json_decode(get_post_meta($post->ID, "kalinsPDFMeta", true));//grab meta from this particular post
	
	if($meta){//if that meta exists, set $showLink
		$showLink = $meta->showLink;
	}else{//if there is no meta for this page/post yet, grab the default
		//$adminOptions = kalins_pdf_get_admin_options();
		//$showLink = $adminOptions['showLink'];
		$showLink = "default";
	}
	
	switch($showLink){//KLUDGE - show radio buttons depending on which one is selected (there should be an easier way than repeating all that HTML - I mean, what if I had like 15 different options?)
		case "top":
			echo '<p><input type="radio" name="kalinsPDFLink" value="top" id="opt_top" checked /> Link at top of page<br /><input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" /> Link at bottom of page<br /><input type="radio" name="kalinsPDFLink" value="none" id="opt_none" /> Do not generate PDF<br /><input type="radio" name="kalinsPDFLink" value="default" id="opt_default" /> Use default</p>';
			break;
		case "bottom":
			echo '<p><input type="radio" name="kalinsPDFLink" value="top" id="opt_top" /> Link at top of page<br /><input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" checked /> Link at bottom of page<br /><input type="radio" name="kalinsPDFLink" value="none" id="opt_none" /> Do not generate PDF<br /><input type="radio" name="kalinsPDFLink" value="default" id="opt_default" /> Use default</p>';
			break;
		case "none":
			echo '<p><input type="radio" name="kalinsPDFLink" value="top" id="opt_top" /> Link at top of page<br /><input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" /> Link at bottom of page<br /><input type="radio" name="kalinsPDFLink" value="none" id="opt_none" checked /> Do not generate PDF<br /><input type="radio" name="kalinsPDFLink" value="default" id="opt_default" /> Use default</p>';
			break;
		case "default":
			echo '<p><input type="radio" name="kalinsPDFLink" value="top" id="opt_top" /> Link at top of page<br /><input type="radio" name="kalinsPDFLink" value="bottom" id="opt_bottom" /> Link at bottom of page<br /><input type="radio" name="kalinsPDFLink" value="none" id="opt_none" /> Do not generate PDF<br /><input type="radio" name="kalinsPDFLink" value="default" id="opt_default" checked /> Use default</p>';
			break;
	}
}

function kalinsPDF_save_postdata( $post_id ) {
	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return $post_id;
	}
	
	if (!isset($_POST['kalinsPDF_noncename'])){//this check is here because the verify_nonce was throwing errors when error reporting was turned on - not sure why the 'DOING_AUTOSAVE' thing didn't catch it
		return $post_id;
	} 
	
	// verify this came from our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['kalinsPDF_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
	
	// Check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ){
		  return $post_id;
		}
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) ){
			return $post_id;
		}
	}

  	// OK, we're authenticated: we need to find and save the data
	$meta = new stdClass();
	$meta->showLink = $_POST['kalinsPDFLink'];
	
	update_post_meta($post_id, 'kalinsPDFMeta', json_encode($meta));
}

function kalinsPDF_publish_post( $post_id ){
	
	kalinsPDF_createPDFDir();
	
	$pdfDir = KALINS_PDF_SINGLES_DIR;
	
	$fileName = $post_id .'.pdf';
	
	if(file_exists($pdfDir .$fileName)){//if the pdf file for this page already exists,
		unlink($pdfDir .$fileName);//delete it cuz it's now out of date since we're saving new post content
	}
	
	$savedPost = get_post($post_id, ARRAY_A);
	$slug = $savedPost['post_name'];
	
	$fileName = $slug .'.pdf';
	
	if(file_exists($pdfDir .$fileName)){//if the pdf file for this page already exists,
		unlink($pdfDir .$fileName);//delete it cuz it's now out of date since we're saving new post content
	}
	
	$adminOptions = kalins_pdf_get_admin_options();
	
	if($adminOptions["autoGenerate"] == "true"){
		$isSingle = true;
		
		$post = get_post($post_id);
		
		if($post->post_type == "page"){
			$pageIDs = "pg_" .$post_id;
		}else{
			$pageIDs = "po_" .$post_id;
		}
		
		$skipReturn = true;
		
		include(WP_PLUGIN_DIR .'/kalins-pdf-creation-station/kalins_pdf_create.php');
	}
	return;
}

function kalinsPDF_content_filter($content){
	
	global $kalinsPDFRunning;
	
	if(isset($kalinsPDFRunning)){
		return $content;
	}
	
	$adminOptions = kalins_pdf_get_admin_options();
	
	if($adminOptions['showOnMulti'] == "false" && !is_single() && !is_page()){//if we're not on a single page/post we don't need to do anything else
		return $content;
	}
	
	global $post;
	
	$meta = json_decode(get_post_meta($post->ID, "kalinsPDFMeta", true));
	
	if($meta){
		$showLink = $meta->showLink;
	}
	
	if(!$meta || $showLink == "default"){
		if(str_word_count(strip_tags($content)) > $adminOptions['wordCount']){//if this post is longer than the minimum word count
			$showLink = $adminOptions['showLink'];
			//return $showLink;
		}else{
			return $content;//if it's not long enough, just quit
		}
	}
	
	if($showLink == "none"){//if we don't want a link or if we're not on a single page/post we don't need to do anything else
		return $content;
	}
	
	//return "wtf?";
	
	$postID = $post->ID;
	
	if($post->post_type == "page"){
		$postID = "pg_" .$postID;
	}else{
		$postID = "po_" .$postID;
	}
	
	//-------remove these three lines if you aren't using shortcodes in the link and you want to conserve processing power
	$adminOptions["beforeLink"] = kalins_pdf_page_shortcode_replace($adminOptions["beforeLink"], $post);
	$adminOptions["linkText"] = kalins_pdf_page_shortcode_replace($adminOptions["linkText"], $post);
	$adminOptions["afterLink"] = kalins_pdf_page_shortcode_replace($adminOptions["afterLink"], $post);
    //-------
	
    $strHtml = $adminOptions["beforeLink"] .'<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/kalins-pdf-creation-station/kalins_pdf_create.php?singlepost=' .$postID .'" target="_blank" >' .$adminOptions["linkText"] .'</a>' .$adminOptions["afterLink"];
    
	switch($showLink){//return the content with the link attached above or below
		case "top":
			return $strHtml .$content;
		case "bottom":
			return $content .$strHtml;
	}
}

/*function kalins_pdf_contextual_help($text, $screen) {
	if (strcmp($screen, 'settings_page_kalins-pdf-creation-station/kalins-pdf-creation-station') == 0 ) {//if we're on settings page, add setting help and return
		require_once( WP_PLUGIN_DIR . '/kalins-pdf-creation-station/kalins_pdf_admin_help.php');
		return;
	}
	
	if (strcmp($screen, 'tools_page_kalins-pdf-creation-station/kalins-pdf-creation-station') == 0 ) {//otherwise show the tool help page (the two help files are very similar but have a few important differences)
		require_once( WP_PLUGIN_DIR . '/kalins-pdf-creation-station/kalins_pdf_tool_help.php');
	}
}*/

function kalins_pdf_contextual_help($contextual_help, $screen_id, $screen) {
	global $kPDFadminPage;
	if($screen_id == $kPDFadminPage){
		$doc = new DOMDocument();
	  $toolHelpFile = $doc->loadHTMLFile(WP_PLUGIN_DIR . '/kalins-pdf-creation-station/kalins_pdf_admin_help.html');
		$contextual_help = $doc->saveHTML();
	}else{
		global $kPDFtoolPage;
		if($screen_id == $kPDFtoolPage){
			$doc = new DOMDocument();
			$toolHelpFile = $doc->loadHTMLFile(WP_PLUGIN_DIR . '/kalins-pdf-creation-station/kalins_pdf_tool_help.html');
			$contextual_help = $doc->saveHTML();
		}
	}
	return $contextual_help;
}


//--------begin ajax calls---------

function kalins_pdf_reset_admin_defaults(){//called when user clicks the reset button on the single admin page
	check_ajax_referer( "kalins_pdf_admin_reset" );
	$kalinsPDFAdminOptions = kalins_pdf_getAdminSettings();
	update_option(KALINS_PDF_ADMIN_OPTIONS_NAME, $kalinsPDFAdminOptions);
	
	//$pdfDir = WP_PLUGIN_DIR . '/kalins-pdf-creation-station/pdf/singles/';//we delete all cached single pdf files since the defaults have probably changed
	//$pdfDir = $pdfDirBase .'singles/';
	//$uploads = wp_upload_dir();
	//$pdfDir = $uploads['basedir'].'/kalins-pdf/singles/';
	
	$pdfDir = KALINS_PDF_SINGLES_DIR;
	
	
	if ($handle = opendir($pdfDir)) {//open pdf directory
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && substr($file, stripos($file, ".")+1, 3) == "pdf") {//loop to find all relevant files 
				unlink($pdfDir .$file);//and delete them
			}
		}
		closedir($handle);
	}
	echo json_encode($kalinsPDFAdminOptions);
}

function kalins_pdf_admin_save(){
	
	check_ajax_referer( "kalins_pdf_admin_save" );
	
	$outputVar = new stdClass();
	
	$kalinsPDFAdminOptions = array();//collect our passed in values so we can save them for next time
	
	$kalinsPDFAdminOptions["beforePage"] = stripslashes($_POST['beforePage']);
	$kalinsPDFAdminOptions["beforePost"] = stripslashes($_POST['beforePost']);
	$kalinsPDFAdminOptions["afterPage"] = stripslashes($_POST['afterPage']);
	$kalinsPDFAdminOptions["afterPost"] = stripslashes($_POST['afterPost']);
	$kalinsPDFAdminOptions["titlePage"] = stripslashes($_POST['titlePage']);
	$kalinsPDFAdminOptions["finalPage"] = stripslashes($_POST['finalPage']);
	$kalinsPDFAdminOptions["headerTitle"] = stripslashes($_POST['headerTitle']);
	$kalinsPDFAdminOptions["headerSub"] = stripslashes($_POST['headerSub']);
	
	$kalinsPDFAdminOptions['linkText'] = stripslashes($_POST['linkText']);
	$kalinsPDFAdminOptions['beforeLink'] = stripslashes($_POST['beforeLink']);
	$kalinsPDFAdminOptions['afterLink'] = stripslashes($_POST['afterLink']);
	
	$kalinsPDFAdminOptions["fontSize"] = (int) $_POST['fontSize'];
	$kalinsPDFAdminOptions['wordCount'] = (int) stripslashes($_POST['wordCount']);
	
	$kalinsPDFAdminOptions['showLink'] = stripslashes($_POST['showLink']);
	
	//echo "AAAAH" .$kalinsPDFAdminOptions['showLink'] ."dkdkdk";
	
	$kalinsPDFAdminOptions["includeImages"] = stripslashes($_POST['includeImages']);
	$kalinsPDFAdminOptions["runShortcodes"] = stripslashes($_POST['runShortcodes']);
	$kalinsPDFAdminOptions["runFilters"] = stripslashes($_POST['runFilters']);
	
	$kalinsPDFAdminOptions["convertYoutube"] = stripslashes($_POST['convertYoutube']);
	$kalinsPDFAdminOptions["convertVimeo"] = stripslashes($_POST['convertVimeo']);
	$kalinsPDFAdminOptions["convertTed"] = stripslashes($_POST['convertTed']);
	
	$kalinsPDFAdminOptions["showOnMulti"] = stripslashes($_POST['showOnMulti']);
	$kalinsPDFAdminOptions["filenameByTitle"] = stripslashes($_POST['filenameByTitle']);
	$kalinsPDFAdminOptions["autoGenerate"] = stripslashes($_POST['autoGenerate']);
	
	//$kalinsPDFAdminOptions["includeTables"] = stripslashes($_POST['includeTables']);
	
	$kalinsPDFAdminOptions["doCleanup"] = stripslashes($_POST['doCleanup']);
	
	
	update_option(KALINS_PDF_ADMIN_OPTIONS_NAME, $kalinsPDFAdminOptions);//save options to database
	
	//$pdfDir = WP_PLUGIN_DIR . '/kalins-pdf-creation-station/pdf/singles/';
	//$pdfDir = $pdfDirBase .'singles/';
	//$uploads = wp_upload_dir();
	//$pdfDir = $uploads['basedir'].'/kalins-pdf/singles/';
	$pdfDir = KALINS_PDF_SINGLES_DIR;
	
	if ($handle = opendir($pdfDir)) {//open pdf directory
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && substr($file, stripos($file, ".")+1, 3) == "pdf") {//loop to find all relevant files 
				unlink($pdfDir .$file);//and delete them
			}
		}
		closedir($handle);
		$outputVar->status = "success";
	}else{
		$outputVar->status = "fail";
	}
	
	echo json_encode($outputVar);
}

function kalins_pdf_tool_defaults(){//called when user clicks the reset button
	check_ajax_referer( "kalins_pdf_tool_reset" );
	$kalinsPDFAdminOptions = kalins_pdf_getDefaultOptions();
	update_option(KALINS_PDF_TOOL_OPTIONS_NAME, $kalinsPDFAdminOptions);
	echo json_encode($kalinsPDFAdminOptions);
}

function kalins_pdf_tool_create(){//called from create button
	check_ajax_referer( "kalins_pdf_tool_create" );
	require_once(WP_PLUGIN_DIR .'/kalins-pdf-creation-station/kalins_pdf_create.php');
}

function kalins_pdf_tool_delete(){//called from either the "Delete All" button or the individual delete buttons
	
	check_ajax_referer( "kalins_pdf_tool_delete" );
	$outputVar = new stdClass();
	$fileName = $_POST["filename"];
	
	$pdfDir = KALINS_PDF_DIR;
		
	if($fileName == "all"){//if we're deleting all of them
		if ($handle = opendir($pdfDir)) {//open pdf directory
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && substr($file, stripos($file, ".")+1, 3) == "pdf") {//loop to find all relevant files 
					unlink($pdfDir .$file);//and delete them
				}
			}
			closedir($handle);
			$outputVar->status = "success";
		}else{
			$outputVar->status = "fail";
		}
	}else{
		$fileName = $pdfDir .$fileName;
		if(file_exists($fileName)){
			unlink($fileName);//delete only the file passed in
			$outputVar->status = "success";
		}else{
			$outputVar->status = "fail";
		}
	}
	echo json_encode($outputVar);
}

function kalins_pdf_create_all(){
	
	$pdfDir = KALINS_PDF_SINGLES_DIR;
	
	check_ajax_referer( "kalins_pdf_create_all" );
	$outputVar = new stdClass();
	kalinsPDF_createPDFDir();
	
	$postLimit = 25;
	$postCount = 0;
	
	$myPosts = get_posts('numberposts=-1&post_type=any');
 	foreach($myPosts as $post) {
    	if(kalinsPDF_build_pdf($post)){
			$postCount = $postCount + 1;
			if($postCount == $postLimit){
				break;
			}
		}
	}
	
	/*if($postCount < $postLimit){ 
		$myPosts = get_posts('numberposts=-1&post_type=page');
		foreach($myPosts as $post) {
			if(kalinsPDF_build_pdf($post)){
				$postCount = $postCount + 1;
				if($postCount == $postLimit){
					break;
				}
			}
		}
	}*/
	
	
	/*if(false){
		$outputVar->status = "success";
	}else{
		$outputVar->status = "fail";
	}*/
	
	/*if($postCount == $postLimit){
		$outputVar->complete = "true";
	}else{
		$outputVar->complete = "";
	}*/
	
	//$outputVar->complete = ($postCount == $postLimit);
	
	$outputVar->createCount = $postCount;
	
	$outputVar->totalCount = wp_count_posts("post")->publish + wp_count_posts("page")->publish;
	
	
	
	$existCount = 0;
	
	if ($handle = opendir($pdfDir)) {//open pdf directory
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && substr($file, stripos($file, ".")+1, 3) == "pdf") {//loop to find all relevant files 
				$existCount = $existCount + 1;//and count them
			}
		}
		closedir($handle);
	}
	
	$outputVar->existCount = $existCount;
	
	$outputVar->status = "success";
	echo json_encode($outputVar);
}

function kalinsPDF_build_pdf( $post ){
	
	$pdfDir = KALINS_PDF_SINGLES_DIR;
	
	$fileName = $post_id .'.pdf';
	
	if(file_exists($pdfDir .$fileName)){//if the pdf file for this page already exists,
		return false;
	}
	
	$savedPost = get_post($post->ID, ARRAY_A);
	$slug = $savedPost['post_name'];
	
	$fileName = $slug .'.pdf';
	
	if(file_exists($pdfDir .$fileName)){//if the pdf file for this page already exists,
		return false;
	}
	
	$isSingle = true;
	
	$skipReturn = true;
	
	if($post->post_type == "page"){
		$pageIDs = "pg_" .$post->ID;
	}else{
		$pageIDs = "po_" .$post->ID;
	}
	
	include(WP_PLUGIN_DIR .'/kalins-pdf-creation-station/kalins_pdf_create.php');
	
	return true;
}


//--------end ajax calls---------

function kalins_pdf_get_tool_options() {
	$kalinsPDFAdminOptions = kalins_pdf_getDefaultOptions();
	
	$devOptions = get_option(KALINS_PDF_TOOL_OPTIONS_NAME);

	if (!empty($devOptions)) {
		foreach ($devOptions as $key => $option){
			$kalinsPDFAdminOptions[$key] = $option;
		}
	}

	update_option(KALINS_PDF_TOOL_OPTIONS_NAME, $kalinsPDFAdminOptions);

	return $kalinsPDFAdminOptions;
}

function kalins_pdf_get_admin_options() {
	$kalinsPDFAdminOptions = kalins_pdf_getAdminSettings();
	
	$devOptions = get_option(KALINS_PDF_ADMIN_OPTIONS_NAME);

	if (!empty($devOptions)) {
		foreach ($devOptions as $key => $option){
			$kalinsPDFAdminOptions[$key] = $option;
		}
	}

	update_option(KALINS_PDF_ADMIN_OPTIONS_NAME, $kalinsPDFAdminOptions);

	return $kalinsPDFAdminOptions;
}

function kalins_pdf_getAdminSettings(){//simply returns all our default option values
	$kalinsPDFAdminOptions = array('headerTitle' => '[post_title] - [post_date]',
		'headerSub' => 'by [post_author] - [blog_name] - [blog_url]',
		'includeImages' => 'false');
	$kalinsPDFAdminOptions['beforePage'] = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';
	$kalinsPDFAdminOptions['beforePost'] = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';;
	$kalinsPDFAdminOptions['afterPage'] = '<p align="center">_______________________________________________</p><p align="center">PDF generated by Kalin\'s PDF Creation Station</p>';
	$kalinsPDFAdminOptions['afterPost'] = '<p align="center">_______________________________________________</p><p align="center">PDF generated by Kalin\'s PDF Creation Station</p>';
	$kalinsPDFAdminOptions['titlePage'] = '';
	$kalinsPDFAdminOptions['finalPage'] = '';
	$kalinsPDFAdminOptions['fontSize'] = 12;
	$kalinsPDFAdminOptions["runShortcodes"] = "false";
	$kalinsPDFAdminOptions["runFilters"] = "false";
	$kalinsPDFAdminOptions["convertYoutube"] = "true";
	$kalinsPDFAdminOptions["convertVimeo"] = "true";
	$kalinsPDFAdminOptions["convertTed"] = "true";
	
	$kalinsPDFAdminOptions["autoGenerate"] = "false";
	$kalinsPDFAdminOptions['showLink'] = "none";
	$kalinsPDFAdminOptions["filenameByTitle"] = "true";
	$kalinsPDFAdminOptions["showOnMulti"] = "false";//filenameByTitle
	$kalinsPDFAdminOptions['linkText'] = "Download [post_title] as PDF";
	$kalinsPDFAdminOptions['beforeLink'] = '<br/><p align="right">-- ';
	$kalinsPDFAdminOptions['afterLink'] = " --</p><br/>";
	$kalinsPDFAdminOptions['doCleanup'] = "true";
	$kalinsPDFAdminOptions['wordCount'] = 0;
	
	return $kalinsPDFAdminOptions;
}

function kalins_pdf_getDefaultOptions(){//simply returns all our default option values
	$kalinsPDFAdminOptions = array('headerTitle' => '[blog_name] - [current_time]',
		'headerSub' => '[blog_description] - [blog_url]',
		'filename' => '[blog_name]',
		'includeImages' => 'false');
	$kalinsPDFAdminOptions['beforePage'] = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';
	$kalinsPDFAdminOptions['beforePost'] = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';;
	$kalinsPDFAdminOptions['afterPage'] = '<p align="center">_______________________________________________</p>';
	$kalinsPDFAdminOptions['afterPost'] = '<p align="center">_______________________________________________</p>';
	$kalinsPDFAdminOptions['titlePage'] = '<p><font size="40">[blog_name]</font></p><p><font size="25">[blog_description]</font></p><p>PDF generated [current_time format="F d, Y"] by Kalin\'s PDF Creation Station WordPress plugin</p>';
	$kalinsPDFAdminOptions['finalPage'] = '<b>[blog_name]</b><p><b>[blog_description]</b></p><p>PDF generated [current_time format="F d, Y \a\t g:i A"] by Kalin\'s PDF Creation Station WordPress plugin</p>';
	$kalinsPDFAdminOptions['fontSize'] = 12;
	$kalinsPDFAdminOptions["runShortcodes"] = "false";
	$kalinsPDFAdminOptions["runFilters"] = "false";
	$kalinsPDFAdminOptions["convertYoutube"] = "true";
	$kalinsPDFAdminOptions["convertVimeo"] = "true";
	$kalinsPDFAdminOptions["convertTed"] = "true";
	$kalinsPDFAdminOptions["autoPageBreak"] = "true";
	$kalinsPDFAdminOptions["includeTOC"] = "true";
	
	
	return $kalinsPDFAdminOptions;
}

function kalins_pdf_cleanup() {//deactivation hook. Clear all traces of PDF Creation Station
	
	$adminOptions = kalins_pdf_get_admin_options();
	if($adminOptions['doCleanup'] == 'true'){//if user set cleanup to true, remove all options and post meta data
		
		delete_option(KALINS_PDF_TOOL_OPTIONS_NAME);
		delete_option(KALINS_PDF_ADMIN_OPTIONS_NAME);//remove all options for admin
		
		$allposts = get_posts();//first get and delete all post meta data
		foreach( $allposts as $postinfo) {
			delete_post_meta($postinfo->ID, 'kalinsPDFMeta');
		}
		
		$allposts = get_pages();//then get and delete all page meta data
		foreach( $allposts as $postinfo) {
			delete_post_meta($postinfo->ID, 'kalinsPDFMeta');
		}
	}
} 

function kalins_pdf_init(){
	//setup internationalization here
	//this doesn't actually run and perhaps there's another better place to do internationalization
}

//----------------begin utility functions-----------------------

//Note: none of these shortcodes are entered into the standard WordPress shortcode system so they only function within Kalin's PDF Creation Station
function kalins_pdf_page_shortcode_replace($str, $page){//replace all passed in shortcodes
	$SCList =  array("[ID]", "[post_title]", "[post_name]", "[guid]", "[comment_count]");//[post_date], "[post_date_gmt]", "[post_modified]", [post_modified_gmt]
	
	$l = count($SCList);
	for($i = 0; $i<$l; $i++){//loop through all page shortcodes (the ones that only work for before/after page/post and refer directly to a page/post attribute)
		$scName = substr($SCList[$i], 1, count($SCList[$i]) - 2);
		$str = str_replace($SCList[$i], $page->$scName, $str);
	}
	
	$str = str_replace("[post_permalink]", get_permalink( $page->ID ), $str);
		
	$postCallback = new KalinsPDF_callback;
		
	if(preg_match('#\[ *post_excerpt *(length=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', $str)){
		
		if($page->post_excerpt == ""){//if there's no excerpt applied to the post, extract one
			
			$postCallback->pageContent = strip_tags($page->post_content);
			$str = preg_replace_callback('#\[ *post_excerpt *(length=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postExcerptCallback'), $str);
			
		}else{//if there is a post excerpt just use it and don't generate our own
			$str = preg_replace('#\[ *post_excerpt *(length=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', $page->post_excerpt, $str);
		}
	}

	$postCallback->curDate = $page->post_date;//change the curDate param and run the regex replace for each type of date/time shortcode
	$str = preg_replace_callback('#\[ *post_date *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postDateCallback'), $str);
	
	$postCallback->curDate = $page->post_date_gmt;
	$str = preg_replace_callback('#\[ *post_date_gmt *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postDateCallback'), $str);
	
	$postCallback->curDate = $page->post_modified;
	$str = preg_replace_callback('#\[ *post_modified *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postDateCallback'), $str);
	
	$postCallback->curDate = $page->post_modified_gmt;
	$str = preg_replace_callback('#\[ *post_modified_gmt *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postDateCallback'), $str);
	
	$postCallback->page = $page;
	
	$str = preg_replace_callback('#\[ *post_author *(type=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postAuthorCallback'), $str);
	
	$str = preg_replace_callback('#\[ *post_meta *(name=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postMetaCallback'), $str);
	
	$str = preg_replace_callback('#\[ *post_categories *(delimeter=[\'|\"]([^\'\"]*)[\'|\"])? *(links=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postCategoriesCallback'), $str);
	
	$str = preg_replace_callback('#\[ *post_tags *(delimeter=[\'|\"]([^\'\"]*)[\'|\"])? *(links=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postTagsCallback'), $str);
	
	$str = preg_replace_callback('#\[ *post_comments *(before=[\'|\"]([^\'\"]*)[\'|\"])? *(after=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'commentCallback'), $str);
	
	$str = preg_replace_callback('#\[ *post_parent *(link=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postParentCallback'), $str);
	
	$str = preg_replace_callback('#\[ *post_thumb *(size=[\'|\"]([^\'\"]*)[\'|\"])? *(extract=[\'|\"]([^\'\"]*)[\'|\"])? *\]#',
			array(&$postCallback, 'postThumbCallback'),
			$str);
	
	
	$str = kalins_pdf_global_shortcode_replace($str);//then parse the global shortcodes
	
	return $str;
}

class KalinsPDF_callback{
	function postExcerptCallback($matches){
		if(isset($matches[2])){
			$exLength = intval($matches[2]);
		}else{
			$exLength = 250;
		}
		
		if(strlen($this->pageContent) > $exLength){
			return htmlspecialchars(strip_shortcodes(substr($this->pageContent, 0, $exLength))) ."...";//clean up and return excerpt
		}else{
			return htmlspecialchars(strip_shortcodes($this->pageContent));
		}
	}
	
	function postDateCallback($matches){
		if(isset($matches[2])){//geez, regex's are awesome. the [2] grabs the second internal portion of the regex, the actual shortcode param value, the () within the ()
			return mysql2date($matches[2], $this->curDate, $translate = true);//translate the wordpress formatted date into whatever date formatting the user passed in
		}else{
			return mysql2date("m-d-Y", $this->curDate, $translate = true);//otherwise do a simple day-month-year format
		}
	}
	
	function postAuthorCallback($matches)
	{
		$userInfo = get_userdata($this->page->post_author);		
		if(isset($matches[2]))
		{
			return $userInfo->$matches[2];
		}
		return $userInfo->display_name;
	}
	
	function postMetaCallback($matches){
		$arr = get_post_meta($this->page->ID, $matches[2]);
		return $arr[0];
	}
	
	function postCategoriesCallback($matches){
		$catString = "";
		
		$categories = get_the_category($this->page->ID);
		$last_item = end($categories);
		
		if(isset($matches[2])){
			$delimeter = $matches[2];
		}else{
			$delimeter = ', ';
		}
		
		if(isset($matches[4]) && strtolower($matches[4]) == 'false'){
			$links = false;
		}else{
			$links = true;
		}
		
		foreach($categories as $category) {
			if($links){
				$catString = $catString .'<a href="' .get_category_link( $category->cat_ID ) .'" >' .$category->cat_name .'</a>';
			}else{
				$catString = $catString .$category->cat_name;
			}
			
			if($category != $last_item){
				$catString = $catString .$delimeter;
			}
		}
		
		return $catString;
	}
	
	function postTagsCallback($matches){
		$catString = "";
		
		$categories = get_the_tags($this->page->ID);
		
		if(!$categories){
			return "";
		}
		
		$last_item = end($categories);
		
		if(isset($matches[2])){
			$delimeter = $matches[2];
		}else{
			$delimeter = ', ';
		}
		
		if(isset($matches[4]) && strtolower($matches[4]) == 'false'){
			$links = false;
		}else{
			$links = true;
		}
		
		foreach($categories as $category) {
			if($links){
				$catString = $catString .'<a href="' .get_tag_link( $category->term_id ) .'" >' .$category->name .'</a>';
			}else{
				$catString = $catString .$category->name;
			}
			
			if($category != $last_item){
				$catString = $catString .$delimeter;
			}
		}
		
		return $catString;
	}
	
	function commentCallback($matches) {
		
		if(defined("KALINS_PDF_COMMENT_CALLBACK")){
			return call_user_func(KALINS_PDF_COMMENT_CALLBACK);
		}
		
		global $post;
		
		$comments = get_comments('status=approve&post_id=' .$post->ID);
		
		$commentString = $matches[2];
		
		foreach($comments as $comment) {
			if($comment->comment_author_url == ""){
				$authorString = $comment->comment_author;
			}else{
				$authorString = '<a href="' .$comment->comment_author_url .'" >' .$comment->comment_author ."</a>";
			}
			$commentString = $commentString .'<p>' .$authorString ."- " .$comment->comment_author_email ." - " .get_comment_date(null, $comment->comment_ID) ." @ " .get_comment_date(get_option('time_format'), $comment->comment_ID) ."<br />" . $comment->comment_content ."</p>";	
		}
		
		//get_comment_date('m-d-Y @ g:i A', $comment->comment_ID) 
		
		return $commentString .$matches[4];
	}
	
	function postParentCallback($matches){
		$parentID = $this->page->post_parent;
		
		if($parentID == 0){
			return "";
		}
		
		if($matches[2] == "false"){
			return get_the_title($parentID);
		}else{
			return '<a href="' .get_permalink( $parentID ) .'" >' .get_the_title($parentID) .'</a>';
		}
	}
	
	function postThumbCallback($matches)
	{
		$imageUrl = "";
		$mode = "none";
		 
		if(isset($matches[4]))
		{
			switch(strtolower($matches[4]))
			{
				case "force":
				case "on":
					$mode = strtolower($matches[4]);
					break;
			}
		}
		 
		if ($mode != "force" && current_theme_supports('post-thumbnails'))
		{
			if(isset($matches[2]))
			{
				$imageSize = $matches[2];
			}
			else
			{
				$imageSize = "full";
			}
	
			//the documentation for wp_get_attachment_image_src says you can pass in an array of width and height but that didn't seem to work.
			//It just returned the size closest to the values I passed in so we are stuck with the four options: thumbnail, medium, large or full
			$arr = wp_get_attachment_image_src(get_post_thumbnail_id($this->page->ID), $imageSize);
	
			$imageUrl = $arr[0];
		}
	
		//if we couldn't find an image and we have an "extract" paramater, search the page content for an image tag and extract its url
		if ($imageUrl == "" && $mode != "none")
		{
			//found two regex's to do this. Not sure which is better so I randomly picked the second one
			//both only grab the first image in the page so I'm not able to do the option where it can randomly select an image from the page instead of just the first one
			//$postImages = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $this->page->post_content, $postMatches);
			$postImages = preg_match_all("/<img .*?(?=src)src=\"([^\"]+)\"/si", $this->page->post_content, $postMatches);
	
			//I couldn't find a way to get the image ID without doing a whole other query so unfortunately we are stuck with the image as it appears in the page and the size parameter does not work here
	
			//if we found an image tag with url
			if(isset($postMatches[1]) && isset($postMatches[1][0]))
			{
				$imageUrl = $postMatches[1][0];
			}
		}
	
		return $imageUrl;
	}
}

function kalinsPDF_timeCallback($matches){
	if(isset($matches[2])){
		return date($matches[2], time());
	}else{
		return date("m-d-Y", time());
	}
}

function kalins_pdf_global_shortcode_replace($str){//replace global shortcodes
	$str = str_replace("[blog_name]", get_option('blogname'), $str);
	$str = str_replace("[blog_description]", get_option('blogdescription'), $str);
	$str = str_replace("[blog_url]", get_option('home'), $str);
	
	//$str = str_replace("[current_time]", date("Y-m-d H:i:s", time()), $str);
	
	$str = preg_replace_callback('#\[ *current_time *(format=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', "kalinsPDF_timeCallback", $str);//this one has its own proprietary function so no need for class and parameters
	
	return $str;
}

function kalinsPDF_createPDFDir(){
	$newDir = KALINS_PDF_DIR;
	
	if(!file_exists($newDir)){
		mkdir($newDir);
	}
	
	if(!file_exists(KALINS_PDF_SINGLES_DIR)){
		mkdir(KALINS_PDF_SINGLES_DIR);
	}
}

/*function kalinsPDF_activate(){
	//echo "test echo";
	
	throw new Exception('Division by zero.');
}*/

//---------------------end utility functions-----------------------------------


//wp actions to get everything started
add_action('admin_init', 'kalins_pdf_admin_init');
add_action('admin_menu', 'kalins_pdf_configure_pages');
//add_action( 'init', 'kalins_pdf_init' );//just keep this for whenever we do internationalization - if the function is actually needed, that is.

add_action('publish_post', 'kalinsPDF_publish_post');
add_action('publish_page', 'kalinsPDF_publish_post');//xmlrpc_publish_post
add_action('xmlrpc_publish_post', 'kalinsPDF_publish_post');
add_action('publish_future_post', 'kalinsPDF_publish_post');

//add_action('transition_post_status', 'kalinsPDF_publish_post', 1);

add_action('save_post', 'kalinsPDF_save_postdata');


//content filter is called whenever a blog page is displayed. Comment this out if you aren't using links applied directly to individual posts, or if the link is set in your theme
add_filter("the_content", "kalinsPDF_content_filter" );


//register_activation_hook( __FILE__, 'kalinsPDF_activate' );


?>