<?php
/*
Plugin Name: Kalin's PDF Creation Station
Version: 4.2.3
Plugin URI: http://kalinbooks.com/pdf-creation-station/
Description: Build highly customizable PDF documents from any combination of pages and posts, or add a link to any page to download a PDF of that post.
Author: Kalin Ringkvist
Author URI: http://kalinbooks.com/

Kalin's PDF Creation station by Kalin Ringkvist (email: kalin@kalinflash.com)

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
define("KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME", "kalins_pdf_tool_template_options");

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

//runs on every admin page
function kalins_pdf_admin_init(){
  
  //not sure why the ajax connections stopped working when put into kalins_admin_page_loaded so they remain here
  //creation tool ajax connections
  add_action('wp_ajax_kalins_pdf_tool_create', 'kalins_pdf_tool_create');
  add_action('wp_ajax_kalins_pdf_tool_save', 'kalins_pdf_tool_save');
  add_action('wp_ajax_kalins_pdf_tool_template_delete', 'kalins_pdf_tool_template_delete');
  add_action('wp_ajax_kalins_pdf_tool_delete', 'kalins_pdf_tool_delete');
  add_action('wp_ajax_kalins_pdf_tool_defaults', 'kalins_pdf_tool_defaults');
  
  //single page admin ajax connections
  add_action('wp_ajax_kalins_pdf_reset_admin_defaults', 'kalins_pdf_reset_admin_defaults');//kalins_pdf_admin_save
  add_action('wp_ajax_kalins_pdf_admin_save', 'kalins_pdf_admin_save');
  add_action('wp_ajax_kalins_pdf_create_all', 'kalins_pdf_create_all');

  //TODO: this should be in a add_meta_boxes action instead of admin_init; need to figure out why that action doesn't work
  add_meta_box( "kalinsPDFNavMenu", "PDF Creation Station", 'kalinsPDF_nav_menu_box', 'nav-menus', 'side');
  
  //--------------you may comment-out the foreach loop if you are using hard-coded PDF links in your theme. This will make your admin panels run slightly more efficiently.-------------
  
  $post_types = get_post_types('','names'); 
  foreach ($post_types as $post_type ) {//loop to add a meta box to each type of post (pages, posts and custom)
    if($post_type != "attachment" && $post_type != "revision" && $post_type != "nav_menu_item"){//don't show metabox for these three post types, if they even have normal edit screens
      add_meta_box( 'kalinsPDF_sectionid', __( "PDF Creation Station", 'kalinsPDF_textdomain' ), 'kalinsPDF_inner_custom_box', $post_type, 'side' );
    }
  }
  //--------------------------------
}

//show the new meta_box in the Appearance->Menus admin page
function kalinsPDF_nav_menu_box(){          
  $count = 1;
  
  if ($handle = opendir(KALINS_PDF_DIR)) {
    //echo '<ul id ="wishlist-login-checklist" class="categorychecklist form-no-clear">';
    while (false !== ($file = readdir($handle))) {
      //loop to find all relevant files (stripos is not case sensitive so it finds .PDF, .HTML, .TXT)
      if ($file != "." && $file != ".." && (stripos($file, ".pdf") > 0 || stripos($file, ".html") > 0 || stripos($file, ".txt") > 0  )) {
        
        if($count === 1){
          echo '<div id="posttype-wl-login" class="posttypediv">
                  <div id="tabs-panel-wishlist-login" class="tabs-panel tabs-panel-active">
                    <ul id ="wishlist-login-checklist" class="categorychecklist form-no-clear">';
        }
        
        //for each file, echo the checkbox with $file label and all the hidden fields needed for the wordpress nav-menu admin to work properly
        echo '<li>
                <label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[' .$count .'][menu-item-object-id]" value="' .$count .'"> ' .$file .'</label>
                <input type="hidden" class="menu-item-type" name="menu-item[' .$count .'][menu-item-type]" value="custom">
                <input type="hidden" class="menu-item-title" name="menu-item[' .$count .'][menu-item-title]" value="' .$file .'">
                <input type="hidden" class="menu-item-url" name="menu-item[' .$count .'][menu-item-url]" value="' .KALINS_PDF_URL .$file .'">
                <input type="hidden" class="menu-item-classes" name="menu-item[' .$count .'][menu-item-classes]" value="wl-login-pop">
              </li>';
        
        $count++;
      }
    }
    closedir($handle);
    
    if($count > 1){
      //echo closing tags and the submit button and 'select all' button. nothing dynamic here.
      echo '</ul>
        </div>
        <p class="button-controls">
          <span class="list-controls">
            <a href="/wordpress/wp-admin/nav-menus.php?page-tab=all&amp;selectall=1#posttype-wl-login" class="select-all">Select All</a>
          </span>
          <span class="add-to-menu">
            <input type="submit" class="button-secondary submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-posttype-wl-login">
            <span class="spinner"></span>
          </span>
        </p>
      </div>';
    }else{
      //if we did not find any files
      echo "You need to create some PDF files in <a href='tools.php?page=kalins-pdf-tool'>Tools-&#62;PDF Creation Station.</a>";
    }
  }else{
    //if we did not find the directory
    echo "You need to create some PDF files in <a href='tools.php?page=kalins-pdf-tool' >Tools-&#62;PDF Creation Station.</a>";
  }
  
}

//runs on every admin page
function kalins_pdf_configure_pages() {
  global $kPDFadminPage;
  
  $kPDFadminPage = add_submenu_page('options-general.php', 'Kalins PDF Creation Station', 'PDF Creation Station', 'manage_options', 'kalins-pdf-admin', 'kalins_pdf_admin_page');
  add_action( 'load-' . $kPDFadminPage , 'kalins_admin_page_loaded' );
  
  global $kPDFtoolPage;
  
  $kPDFtoolPage = add_submenu_page('tools.php', 'Kalins PDF Creation Station', 'PDF Creation Station', 'manage_options', 'kalins-pdf-tool', 'kalins_pdf_tool_page');
  add_action( 'load-' . $kPDFtoolPage , 'kalins_admin_page_loaded' );
}

//runs just on our tool and settings page
function kalins_admin_page_loaded(){  
  global $kPDFadminPage;
  global $kPDFtoolPage;
  
  add_action( "admin_print_scripts-$kPDFadminPage", 'kalins_pdf_admin_head' );
  add_action('admin_print_styles-' . $kPDFadminPage, 'kalins_pdf_admin_styles');
  
  add_action( "admin_print_scripts-$kPDFtoolPage", 'kalins_pdf_admin_head' );
  add_action('admin_print_styles-' . $kPDFtoolPage, 'kalins_pdf_admin_styles');
  
  add_filter('contextual_help', 'kalins_pdf_contextual_help', 10, 3);
  
  wp_register_style('kalinPDFBootstrapStyle', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css');
  
  wp_register_style('kalinPDFStyle', plugins_url('kalins_pdf_styles.css', __FILE__));// . '/kalins-pdf-creation-station/kalins_pdf_styles.css');
  wp_register_script( 'kalinPDFVendors', plugins_url('vendor.min.js', __FILE__ )); 
  wp_register_script( 'kalinPDF_KalinsUIService', plugins_url('KalinsUIService.js', __FILE__));
}

function kalins_pdf_admin_head() {  
  wp_enqueue_script( "jquery-ui-sortable");
  wp_enqueue_script( 'kalinPDFVendors' );
  wp_enqueue_script( 'kalinPDF_KalinsUIService' );
}

function kalins_pdf_admin_styles(){//not sure why this didn't work if called from pdf_admin_head    
  wp_enqueue_style('kalinPDFBootstrapStyle');
  wp_enqueue_style('kalinPDFStyle');
}

function kalinsPDF_inner_custom_box($post) {//creates the box that goes on the post/page edit page
  // show nonce for verification and post box label
  echo '<input type="hidden" name="kalinsPDF_noncename" id="kalinsPDF_noncename" value="' .wp_create_nonce( plugin_basename(__FILE__) ) . '" />Create PDF of this page? <br />';
  
  $meta = json_decode(get_post_meta($post->ID, "kalinsPDFMeta", true));//grab meta from this particular post
  
  if($meta){//if that meta exists, set $showLink
    $showLink = $meta->showLink;
  }else{//if there is no meta for this page/post yet, grab the default
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
  
  $adminOptions = kalins_pdf_get_options(KALINS_PDF_ADMIN_OPTIONS_NAME);
  
  if($adminOptions->autoGenerate){
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

// This is very similar to widget() but with important differences. If you make any changes here, make
// sure they don't also need to be made there
function kalinsPDF_content_filter($content){
  
  //set in kalins_pdf_create.php, in case the user just clicked a split second earlier and it's still processing
  global $kalinsPDFRunning;
  if(isset($kalinsPDFRunning)){
    return $content;
  }
  
  $adminOptions = kalins_pdf_get_options(KALINS_PDF_ADMIN_OPTIONS_NAME);
  if(!$adminOptions->showOnMulti && !is_single() && !is_page()){//if we're not on a single page/post we don't need to do anything else
    return $content;
  }
  
  global $post;
  
  $meta = json_decode(get_post_meta($post->ID, "kalinsPDFMeta", true));
  
  if($meta){
    $showLink = $meta->showLink;
  }
  
  if(!$meta || $showLink == "default"){
    if(str_word_count(strip_tags($content)) > $adminOptions->wordCount){//if this post is longer than the minimum word count
      $showLink = $adminOptions->showLink;
    }else{
      return $content;//if it's not long enough, just quit
    }
  }
  
  if($showLink == "none"){//if we don't want a link or if we're not on a single page/post we don't need to do anything else
    return $content;
  }
    
  $postID = $post->ID;
  
  if($post->post_type == "page"){
    $postID = "pg_" .$postID;
  }else{
    $postID = "po_" .$postID;
  }
  
  $adminOptions->beforeLink = kalins_pdf_page_shortcode_replace($adminOptions->beforeLink, $post);
  $adminOptions->linkText = kalins_pdf_page_shortcode_replace($adminOptions->linkText, $post);
  $adminOptions->afterLink = kalins_pdf_page_shortcode_replace($adminOptions->afterLink, $post);
  
  $strHtml = $adminOptions->beforeLink .'<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/kalins-pdf-creation-station/kalins_pdf_create.php?singlepost=' .$postID .'" target="_blank" >' .$adminOptions->linkText .'</a>' .$adminOptions->afterLink;
    
  switch($showLink){//return the content with the link attached above or below
    case "top":
      return $strHtml .$content;
    case "bottom":
      return $content .$strHtml;
  }
}

function kalins_pdf_contextual_help($contextual_help, $screen_id, $screen) {
  global $kPDFadminPage;
  if($screen_id == $kPDFadminPage){
    $sAdminHelp = file_get_contents(WP_PLUGIN_DIR . '/kalins-pdf-creation-station/help/kalins_pdf_admin_help.html');

    //split the file in two based on the marker I put in the html file
    $sOverview = substr($sAdminHelp, 0, strpos($sAdminHelp, "<!--0-->"));
    $sAdvanced = substr($sAdminHelp, strpos($sAdminHelp, "<!--0-->") + 8);

    $screen = get_current_screen();
    $screen->add_help_tab( array(
        'id' => "kAdminHelp1",   //unique id for the tab
        'title' => "Overview",   //unique visible title for the tab
        'content' => $sOverview  //actual help text
    ) );

    $screen->add_help_tab( array(
        'id' => "kAdminHelp2",   //unique id for the tab
        'title' => "Advanced",   //unique visible title for the tab
        'content' => $sAdvanced  //actual help text
    ) );
  }else{
    global $kPDFtoolPage;
    if($screen_id == $kPDFtoolPage){
      $sToolHelp = file_get_contents(WP_PLUGIN_DIR . '/kalins-pdf-creation-station/help/kalins_pdf_tool_help.html');

      //split the file in two based on the marker I put in the html file
      $sOverview = substr($sToolHelp, 0, strpos($sToolHelp, "<!--0-->"));
      $sAdvanced = substr($sToolHelp, strpos($sToolHelp, "<!--0-->") + 8);

      $screen = get_current_screen();
      $screen->add_help_tab( array(
          'id' => "kToolHelp1",     //unique id for the tab
          'title' => "Overview",    //unique visible title for the tab
          'content' => $sOverview   //actual help text
      ) );

      $screen->add_help_tab( array(
          'id' => "kToolHelp2",     //unique id for the tab
          'title' => "Advanced",    //unique visible title for the tab
          'content' => $sAdvanced   //actual help text
      ) );
      
    }
  }
  
  $sAbout = file_get_contents(WP_PLUGIN_DIR . '/kalins-pdf-creation-station/help/about.html');
  $screen->add_help_tab( array(
  		'id' => "kAboutHelp",     //unique id for the tab
  		'title' => "About",      //unique visible title for the tab
  		'content' => $sAbout  //actual help text
  ) );
  
  return $contextual_help;
}


//--------begin ajax calls---------

function kalins_pdf_reset_admin_defaults(){//called when user clicks the reset button on the single admin page
  check_ajax_referer( "kalins_pdf_admin_reset" );
  $kalinsPDFAdminOptions = kalins_pdf_getAdminSettings();
  update_option(KALINS_PDF_ADMIN_OPTIONS_NAME, $kalinsPDFAdminOptions);

  $pdfDir = KALINS_PDF_SINGLES_DIR;
  
  if ($handle = opendir($pdfDir)) {//open pdf directory
    while (false !== ($file = readdir($handle))) {
      if ($file != "." && $file != ".." && substr($file, stripos($file, ".")+1, 3) == "pdf") {//loop to find all relevant files 
        unlink($pdfDir .$file);//and delete them
      }
    }
    closedir($handle);
  }
  die(json_encode($kalinsPDFAdminOptions));
}

function kalins_pdf_admin_save(){
  check_ajax_referer( "kalins_pdf_admin_save" );
  
  //decode our JSON after stripping off the slashes that get added in the request
  $request_body = json_decode(trim(file_get_contents('php://input')));  
  update_option(KALINS_PDF_ADMIN_OPTIONS_NAME, $request_body->oOptions);//save options to database
  
  $pdfDir = KALINS_PDF_SINGLES_DIR;  
  if ($handle = opendir($pdfDir)) {//open pdf directory
    while (false !== ($file = readdir($handle))) {
      if ($file != "." && $file != ".." && substr($file, stripos($file, ".")+1, 3) == "pdf") {//loop to find all relevant files 
        unlink($pdfDir .$file);//and delete them
      }
    }
    closedir($handle);
    die("success");
  }else{
    die("Save failed for unknown reason.");
  }
}

function kalins_pdf_tool_create(){//called from create button
  check_ajax_referer( "kalins_pdf_tool_create" );
  require_once(WP_PLUGIN_DIR .'/kalins-pdf-creation-station/kalins_pdf_create.php');
}

function kalins_pdf_tool_save(){//called from tool page save template button
  check_ajax_referer( "kalins_pdf_tool_save" );
  
  $request_body = json_decode(trim(file_get_contents('php://input')));
  $newTemplateSettings =  $request_body->oOptions;

  $newTemplateSettings->date = date("Y-m-d H:i:s", time());//add save date
  
  //get the array of templates
  $templates = kalins_pdf_get_options( KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME );
  
  //remember this name so that it's kept as the current template so it's automatically loaded next time the page loads
  $templates->sCurTemplate = $newTemplateSettings->templateName;
  
  $bFound = false;
  $l = count($templates->aTemplates);
  for($i = 0; $i < $l; $i++){
    if($templates->aTemplates[$i]->templateName === $newTemplateSettings->templateName){
      $templates->aTemplates[$i] = $newTemplateSettings;
      $bFound = true;
      break;
    }
  }
  
  if(!$bFound){
    array_push($templates->aTemplates, $newTemplateSettings);
  }
  
  //save the result back to the database
  update_option(KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME, $templates);
  
  $outputVar = new stdClass();
  $outputVar->status = "success";
  $outputVar->newTemplate = $newTemplateSettings;
  
  //send our new object back to the client so we can add it to our list
  die(json_encode($outputVar));
}

function kalins_pdf_tool_template_delete(){//called from either the "Delete All" button or the individual delete buttons
  check_ajax_referer( "kalins_pdf_tool_template_delete" );
  
  $request_body = json_decode(trim(file_get_contents('php://input')));
  $templateName =  $request_body->templateName;
  
  if($templateName === "all"){
    //create new template object, list array and add its "original defaults" template back in
    $templates = new stdClass();
    $templates->aTemplates = array();
    $templates->aTemplates[0] = kalins_pdf_getToolSettings();
    $templates->sCurTemplate = "original defaults";
  }else{
    $templates = kalins_pdf_get_options( KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME );    
    $l = count($templates->aTemplates);
    for($i = 0; $i < $l; $i++){//loop through all template items
      if($templates->aTemplates[$i]->templateName === $templateName){//if our name matches    
        array_splice($templates->aTemplates, $i, 1);//delete the item in the array
        break;
      }
    }
    
    //if we deleted our currently viewed template, set sCurTemplate to the default object that can't be deleted
    if($templateName === $templates->sCurTemplate){
      $templates->sCurTemplate = "original defaults";
    }
  }
  update_option(KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME, $templates);
  die("success");
}

function kalins_pdf_tool_delete(){//called from either the "Delete All" button or the individual delete buttons
  
  check_ajax_referer( "kalins_pdf_tool_delete" );
  $outputVar = new stdClass();
  
  $request_body = json_decode(trim(file_get_contents('php://input')));  
  $filename = $request_body->filename;
  
  $pdfDir = KALINS_PDF_DIR;
    
  if($filename === "all"){//if we're deleting all of them
    if ($handle = opendir($pdfDir)) {//open pdf directory
      while (false !== ($file = readdir($handle))) {
        //loop to find all relevant files
        if ($file != "." && $file != ".." && (stripos($file, ".pdf") > 0 || stripos($file, ".html") > 0 || stripos($file, ".txt") > 0)) { 
          unlink($pdfDir .$file);//and delete them
        }
      }
      closedir($handle);
      $outputVar->status = "success";
    }else{
      $outputVar->status = "fail";
    }
  }else{
    $filename = $pdfDir .$filename;
    if(file_exists($filename)){
      unlink($filename);//delete only the file passed in
      $outputVar->status = "success";
    }else{
      $outputVar->status = "fail";
    }
  }
  die(json_encode($outputVar));
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
  die(json_encode($outputVar));
}

function kalinsPDF_build_pdf( $post ){
  
  $pdfDir = KALINS_PDF_SINGLES_DIR;
    
  $fileName = $post->post_name .'.pdf';
  
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

//this function basically serves as our database gateway for all our Creation Station options
function kalins_pdf_get_options( $sOptionsName ) {
  //get our previously saved settings
  $devOptions = get_option( $sOptionsName );

  if (empty($devOptions)) {
    //if we don't have any saved settings (like this is the first time this plugin is used), then get our defaults
    switch ($sOptionsName){
      case KALINS_PDF_ADMIN_OPTIONS_NAME:
        $devOptions = kalins_pdf_getAdminSettings();//get default admin settings
        break;
      case KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME:
        $devOptions = new stdClass();//set to empty object since we have no default saved templates (but maybe we will someday)
        $devOptions->aTemplates = array();
        $devOptions->aTemplates[0] = kalins_pdf_getToolSettings();
        $devOptions->aTemplates[0]->date = date("Y-m-d H:i:s", time());//add save date
        
        $oldOptions = get_option( KALINS_PDF_TOOL_OPTIONS_NAME );
        
        //if we have the old options object available, 
        if(!empty($oldOptions)){
          $oldOptions = (object) $oldOptions;//old options were an associative array so convert to object
          $oldOptions->templateName = "previous settings";//give it a name that hopefully the user can understand
          $oldOptions->buildPostList = array();//and give it its empty buildPostList which wasn't present on the main object in v4.0
          $oldOptions->date = date("Y-m-d H:i:s", time());//add save date
          
          $devOptions->aTemplates[1] = $oldOptions; //copy old settings onto the new array,
          $devOptions->sCurTemplate = "previous settings";
          delete_option(KALINS_PDF_TOOL_OPTIONS_NAME);// permanently delete the old settings
        }else{
          //if there are no options saved from v 4.0 (i.e. when this is the first time they've ever been to the tools page), we set the default object from kalins_pdf_getToolSettings() to be our current template
          $devOptions->sCurTemplate = "original defaults";
        }        
        break;
    }

    update_option( $sOptionsName, $devOptions );
  }
  return (object) $devOptions;//typecast to object just in case we're using the one from <= v4.0
}

function kalins_pdf_getAdminSettings(){//simply returns all our default option values for settings page
  $kalinsPDFAdminOptions = new stdClass();
  $kalinsPDFAdminOptions->headerTitle = '[post_title] - [post_date]';
  $kalinsPDFAdminOptions->headerSub = 'by [post_author] - [blog_name] - [blog_url]';
  $kalinsPDFAdminOptions->beforePage = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';
  $kalinsPDFAdminOptions->beforePost = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';;
  $kalinsPDFAdminOptions->afterPage = '<p align="center">_______________________________________________</p><p align="center">PDF generated by Kalin\'s PDF Creation Station</p>';
  $kalinsPDFAdminOptions->afterPost = '<p align="center">_______________________________________________</p><p align="center">PDF generated by Kalin\'s PDF Creation Station</p>';
  $kalinsPDFAdminOptions->titlePage = '';
  $kalinsPDFAdminOptions->finalPage = '';
  $kalinsPDFAdminOptions->fontSize = 12;
  $kalinsPDFAdminOptions->includeImages = false;
  $kalinsPDFAdminOptions->runShortcodes = false;
  $kalinsPDFAdminOptions->runFilters = false;
  $kalinsPDFAdminOptions->convertYoutube = true;
  $kalinsPDFAdminOptions->convertVimeo = true;
  $kalinsPDFAdminOptions->convertTed = true;
  
  //admin page specific properties:
  $kalinsPDFAdminOptions->autoGenerate = false;
  $kalinsPDFAdminOptions->showLink = "none";
  $kalinsPDFAdminOptions->filenameByTitle = true;
  $kalinsPDFAdminOptions->showOnMulti = false;
  $kalinsPDFAdminOptions->linkText = "Download [post_title] as PDF";
  $kalinsPDFAdminOptions->beforeLink = '<br/><p align="right">-- ';
  $kalinsPDFAdminOptions->afterLink = " --</p><br/>";
  $kalinsPDFAdminOptions->doCleanup = true;
  $kalinsPDFAdminOptions->wordCount = 0;
  
  return $kalinsPDFAdminOptions;
}

function kalins_pdf_getToolSettings(){//simply returns all our default option values for tool page
  $kalinsPDFAdminOptions = new stdClass();
  $kalinsPDFAdminOptions->headerTitle = '[blog_name] - [current_time]';
  $kalinsPDFAdminOptions->headerSub = '[blog_description] - [blog_url]';
  $kalinsPDFAdminOptions->beforePage = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';
  $kalinsPDFAdminOptions->beforePost = '<h1>[post_title]</h1><p><b>by [post_author] - [post_date  format="l, F d, Y"]</b></p><p><a href="[post_permalink]">[post_permalink]</a></p>';;
  $kalinsPDFAdminOptions->afterPage = '<p align="center">_______________________________________________</p>';
  $kalinsPDFAdminOptions->afterPost = '<p align="center">_______________________________________________</p>';
  $kalinsPDFAdminOptions->titlePage = '<p><font size="40">[blog_name]</font></p><p><font size="25">[blog_description]</font></p><p>PDF generated [current_time format="F d, Y"] by Kalin\'s PDF Creation Station WordPress plugin</p>';
  $kalinsPDFAdminOptions->finalPage = '<b>[blog_name]</b><p><b>[blog_description]</b></p><p>PDF generated [current_time format="F d, Y \a\t g:i A"] by Kalin\'s PDF Creation Station WordPress plugin</p>';
  $kalinsPDFAdminOptions->fontSize = 12;
  $kalinsPDFAdminOptions->includeImages = false;
  $kalinsPDFAdminOptions->runShortcodes = false;
  $kalinsPDFAdminOptions->runFilters = false;
  $kalinsPDFAdminOptions->convertYoutube = true;
  $kalinsPDFAdminOptions->convertVimeo = true;
  $kalinsPDFAdminOptions->convertTed = true;
  
  //tool page specific properties:
  $kalinsPDFAdminOptions->filename = '[blog_name]';
  $kalinsPDFAdminOptions->templateName = '';
  $kalinsPDFAdminOptions->buildPostList = array();
  $kalinsPDFAdminOptions->autoPageBreak = true;
  $kalinsPDFAdminOptions->includeTOC = true;
  $kalinsPDFAdminOptions->bCreatePDF = true;
  $kalinsPDFAdminOptions->bCreateHTML = false;
  $kalinsPDFAdminOptions->bCreateTXT = false;
  
  $kalinsPDFAdminOptions->templateName = "original defaults";
  
  return $kalinsPDFAdminOptions;
}

function kalins_pdf_cleanup() {//deactivation hook. Clear all traces of PDF Creation Station
  
  $adminOptions = kalins_pdf_get_options(KALINS_PDF_ADMIN_OPTIONS_NAME);
    
  if($adminOptions->doCleanup){//if user set cleanup to true, remove all options and post meta data
    delete_option(KALINS_PDF_TOOL_OPTIONS_NAME);//keep this just in case they still have this and never went to the tool page to get it updated
    delete_option(KALINS_PDF_ADMIN_OPTIONS_NAME);//remove all options for admin
    delete_option(KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME);
    
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
  
  $str = preg_replace_callback('#\[ *post_thumb *(size=[\'|\"]([^\'\"]*)[\'|\"])? *(extract=[\'|\"]([^\'\"]*)[\'|\"])? *\]#', array(&$postCallback, 'postThumbCallback'), $str);
  
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

//---------------------end utility functions-----------------------------------

class WP_Kalins_PDF_Creation_Station_Widget extends WP_Widget {

  function WP_Kalins_PDF_Creation_Station_Widget() {
    $widget_ops = array( 'classname' => 'widget_KalinsPDFCreationStation', 'description' => __( "Show a link to the PDF version of the post or page" ) );
    $this->WP_Widget('kalinsPDFCreationStation', __("PDF Creation Station"), $widget_ops);
  }

  // This code displays the user-facing widget
  // This is very similar to kalinsPDF_content_filter but with important differences. If you make any changes here, make
  // sure they don't also need to be made there
  function widget($args, $instance) {
    //set in kalins_pdf_create.php, in case the user just clicked a split second earlier and it's still processing
    global $kalinsPDFRunning;
    if(isset($kalinsPDFRunning)){
      return "";
    }
    
    if(!is_single() && !is_page()){//if we're not on a page or post we don't show the widget
      return "";
    }
    
    $adminOptions = kalins_pdf_get_options(KALINS_PDF_ADMIN_OPTIONS_NAME);
    
    global $post;
    
    $meta = json_decode(get_post_meta($post->ID, "kalinsPDFMeta", true));
    
    if($meta){
      $showLink = $meta->showLink;
    }
    
    if(!$meta || $showLink == "default"){
      if(str_word_count(strip_tags($post->post_content)) > $adminOptions->wordCount){//if this post is longer than the minimum word count
        $showLink = $adminOptions->showLink;
      }else{
        return "";//if it's not long enough, just quit
      }
    }
    
    if($showLink === "none"){//if we don't want a link or if we're not on a single page/post we don't need to do anything else
      return "";
    }
    
    //at this point we know we're going to show a link
    extract($args);

    //change postID to identify if it's a page or post for use by our create script
    $postID = $post->ID;
    if($post->post_type == "page"){
      $postID = "pg_" .$postID;
    }else{
      $postID = "po_" .$postID;
    }

    //if we have not saved an option, use the default and run it's shortcode conversion; else grab the widget setting and run its shortcode conversion
    if(empty($instance['beforeLink'])){
      $adminOptions->beforeLink = kalins_pdf_page_shortcode_replace($adminOptions->beforeLink, $post);
    }else{
      $adminOptions->beforeLink = kalins_pdf_page_shortcode_replace($instance["beforeLink"], $post);
    }
    
    if(empty($instance['linkText'])){
      $adminOptions->linkText = kalins_pdf_page_shortcode_replace($adminOptions->linkText, $post);        
    }else{
      $adminOptions->linkText = kalins_pdf_page_shortcode_replace($instance["linkText"], $post);
    }
    
    if(empty($instance['afterLink'])){
      $adminOptions->afterLink = kalins_pdf_page_shortcode_replace($adminOptions->afterLink, $post);        
    }else{
      $adminOptions->afterLink = kalins_pdf_page_shortcode_replace($instance["afterLink"], $post);
    }
    
    //begin echoing content to user-facing widget
    echo $before_widget;//echo wordpress' standard html
    if(!empty($instance['title'])) {//only show the title if this widget has one
      echo $before_title . $instance['title'] . $after_title;
    }
    
    echo $adminOptions->beforeLink .'<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/kalins-pdf-creation-station/kalins_pdf_create.php?singlepost=' .$postID .'" target="_blank" >' .$adminOptions->linkText .'</a>' .$adminOptions->afterLink;
    echo $after_widget;
  }

  // Updates the settings.
  function update($new_instance, $old_instance) {
    return $new_instance;
  }

  //this code displays the admin-facing widget interface
  function form($instance) {
    echo '<div>';
    //create three lists of data for each text field
    $aTextFieldLabels = array("Title:", "Link text:", "Before link:", "After link:");
    $aTextFieldNames = array("title", "linkText", "beforeLink", "afterLink");
    $adminOptions = kalins_pdf_get_options(KALINS_PDF_ADMIN_OPTIONS_NAME);
    $aTextFieldDefaultValues = array("", $adminOptions->linkText, $adminOptions->beforeLink, $adminOptions->afterLink);
    
    //loop to add each of our four textfields to the widget form
    for($i=0; $i<4; $i++){
      $sFieldName = $aTextFieldNames[$i];
      
      $sFieldValue = $aTextFieldDefaultValues[$i];
      //if we have already saved a value, use it instead of the default
      if(isset($instance[$sFieldName])){
        $sFieldValue = $instance[$sFieldName];
      }
      
      //apostrophes will mess everything up when echoed, so we escape them
      $sFieldValue = str_replace("'", "&#39;", $sFieldValue);
      
      //begin echoing content to admin-facing interface 
      echo '<label for="' . $this->get_field_id($sFieldName) .'">' .$aTextFieldLabels[$i] .'</label>';
      echo '<input type="text" class="widefat" ';
      echo 'name="' . $this->get_field_name($sFieldName) . '" ';
      echo 'id="' . $this->get_field_id($sFieldName) . '" ';
      echo "value='" . $sFieldValue . "' /><br/><br/>";
    }
    
    echo '<br/><br/></div>';

  } // end function form

} // end class WP_Widget_BareBones

//wp actions to get everything started

add_action('widgets_init', create_function('', 'return register_widget("WP_Kalins_PDF_Creation_Station_Widget");'));// Register the widget.
add_action('admin_init', 'kalins_pdf_admin_init');
add_action('admin_menu', 'kalins_pdf_configure_pages');
add_action('publish_post', 'kalinsPDF_publish_post');//runs action on post publish
add_action('publish_page', 'kalinsPDF_publish_post');//runs action on page publish
add_action('xmlrpc_publish_post', 'kalinsPDF_publish_post');
add_action('publish_future_post', 'kalinsPDF_publish_post');

add_action('save_post', 'kalinsPDF_save_postdata');

//content filter is called whenever a blog page is displayed. Comment this out if you aren't using links applied directly to individual posts, or if the link is set in your theme
add_filter("the_content", "kalinsPDF_content_filter" );

register_uninstall_hook( __FILE__, 'kalins_pdf_cleanup' );

?>