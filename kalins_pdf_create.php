<?php


$outputVar = new stdClass();

if(!isset($isSingle)){
  $isSingle = isset($_GET["singlepost"]);
}

try{
  if($isSingle && !isset($pageIDs)){//guess I don't know enough about PHP to understand why this page thinks its in a different location in relation to wp-config depending on how its called... but somehow always knows how to get tcpdf
    require_once("../../../wp-config.php");
  }else{
    //require_once("../wp-config.php");
  }
  
  require_once('tcpdf/tcpdf.php');
} catch (Exception $e) {
  $outputVar->status = "problem loading wp-config or TCPDF library.";
  die(json_encode($outputVar));
}

kalinsPDF_createPDFDir();

global $wpdb, $post;

if($isSingle){
  $oOptions = kalins_pdf_get_options(KALINS_PDF_ADMIN_OPTIONS_NAME);
  
  if(!isset($pageIDs)){
    $pageIDs = $_GET["singlepost"];
  }
  
  $singleID = substr($pageIDs, 3);

  $pdfDir = KALINS_PDF_SINGLES_DIR;
  $pdfURL = KALINS_PDF_SINGLES_URL;
  
  if($oOptions->filenameByTitle){
    
    $singlePost = "";
        
    if(substr($pageIDs, 0, 2) == "po"){
      $singlePost = get_post($singleID);
    }else{
      $singlePost = get_page($singleID);
    }
    
    $filename = $singlePost->post_name;
    
  }else{
    $filename = $singleID;
  }
  
  if(file_exists($pdfDir .$filename .".pdf")){//if the file already exists, simply redirect to that file and we're done
    if(!isset($skipReturn)){
      header("Location: " .$pdfURL .$filename .".pdf");
    }
    die();
  }else{
    $outputVar->fileName = $filename .".pdf";
    $outputVar->date = date("Y-m-d H:i:s", time());
    
    $oOptions->autoPageBreak = true;
    $includeTOC = false;//singles don't get no Table of contents
  }
}else{
  try{    
    $pdfDir = KALINS_PDF_DIR;
  
    $request_body = json_decode(trim(file_get_contents('php://input')));
    $oOptions =  $request_body->oOptions;
    
    //save this
    $templates = kalins_pdf_get_options( KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME );
    $templates->sCurTemplate = $oOptions->templateName;
    update_option(KALINS_PDF_TOOL_TEMPLATE_OPTIONS_NAME, $templates);
    
    //$pageIDs are sent on the request instead of the oOptions object because they are not saved to the database
    $pageIDs = $request_body->pageIDs;
    $includeTOC = $oOptions->includeTOC;
    
    if($oOptions->filename != ""){
      $filename = kalins_pdf_global_shortcode_replace($oOptions->filename);//&#039;
      $filename = str_replace("&#039;", "", $filename);//remove all apostrophes from filename
      $filename = str_replace("\'", "", $filename);
    }else{
      //if user did not enter a filename, we use the current timestamp as a filename (mostly just to streamline testing) 
      $filename = time();
    }
  } catch (Exception $e) {
    $outputVar->status = "problem setting options. Be sure the text you have entered is compatible or try resetting to defaults.";
    die(json_encode($outputVar));
  }
  
  $outputVar->aFiles = array();
  function processFileType($fileType, $filename, $outputVar, $pdfDir){
    if(file_exists($pdfDir .$filename .$fileType)){//if a file already exists, error and quit
      $outputVar->status = $filename .$fileType ." already exists.";
      die(json_encode($outputVar));
    }
    //add array of new filenames/dates to the result object
    $newFileObj = new stdClass();
    $newFileObj->fileName = $filename .$fileType;
    $newFileObj->date = date("Y-m-d H:i:s", time());
    array_push($outputVar->aFiles, $newFileObj);
  }

  if($oOptions->bCreateHTML){
    processFileType(".html", $filename, $outputVar, $pdfDir);
  }

  if($oOptions->bCreatePDF){
    processFileType(".pdf", $filename, $outputVar, $pdfDir);
  }
  
  if($oOptions->bCreateTXT){
    processFileType(".txt", $filename, $outputVar, $pdfDir);
  }
}

//these are properties from our $oOptions object that are used more than twice AND are handled the same for both 
//single and multi. Properties used <3 times are just grabbed on the fly from $oOptions
$titlePage = $oOptions->titlePage;
$finalPage = $oOptions->finalPage;
$headerTitle = $oOptions->headerTitle;
$headerSub = $oOptions->headerSub;
$fontSize = $oOptions->fontSize;

$result = array ();

try{
  
  $pageArr = explode(",", $pageIDs);
  $le = count($pageArr);
  
  for($i = 0; $i < $le; $i++){
    if(substr($pageArr[$i], 0, 2) == "po"){
      array_push($result, get_post(substr($pageArr[$i], 3)));
    }else{
      array_push($result, get_page(substr($pageArr[$i], 3)));
    }
  }
} catch (Exception $e) {
  $outputVar->status = "problem getting pages and posts.";
  die(json_encode($outputVar));
}

try{
  // create new PDF document
  $objTcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);
  // set document information
  $objTcpdf->SetCreator(PDF_CREATOR);
  
  $theSubTitle = "";
  $theTitle = "";
  
  if($isSingle){
    $theTitle = htmlspecialchars_decode(kalins_pdf_page_shortcode_replace($headerTitle, $result[0]));
    $theTitle = str_replace("&#039;", "'", $theTitle);//manually replace apostrophes (htmlspecialchars_decode didn't work for that one)
    $theSubTitle = htmlspecialchars_decode(kalins_pdf_page_shortcode_replace($headerSub, $result[0]));
    $theSubTitle = str_replace("&#039;", "'", $theSubTitle);
    $objTcpdf->SetTitle( $theTitle );// set default header data
    $objTcpdf->SetHeaderData(null, null, $theTitle, $theSubTitle);
  }else{
    $theTitle = htmlspecialchars_decode(kalins_pdf_global_shortcode_replace($headerTitle));
    $theTitle = str_replace("&#039;", "'", $theTitle);//manually replace apostrophes
    $theSubTitle = htmlspecialchars_decode(kalins_pdf_global_shortcode_replace($headerSub));
    $theSubTitle = str_replace("&#039;", "'", $theSubTitle);
    $objTcpdf->SetTitle( $theTitle );// set default header data
    $objTcpdf->SetHeaderData(null, null, $theTitle, $theSubTitle );
  }
  // set header and footer fonts
  $objTcpdf->setHeaderFont(Array('Times', '', PDF_FONT_SIZE_MAIN));
  $objTcpdf->setFooterFont(Array('Times', '', PDF_FONT_SIZE_DATA));
  //set margins
  $objTcpdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
  $objTcpdf->SetHeaderMargin(PDF_MARGIN_HEADER);
  $objTcpdf->SetFooterMargin(PDF_MARGIN_FOOTER);
  //set auto page breaks
  $objTcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
  //set image scale factor
  $objTcpdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
  
  
  //set some language-dependent strings
  //$lg['a_meta_charset'] = 'UTF-8';
  //$lg['a_meta_dir'] = 'rtl';
  //$lg['a_meta_language'] = 'fa';
  
  /* to translate or otherwise change the word 'page', add the following code into your wp-config.php file. This will retain the setting even as I upgrade the plugin.
  
  define("KALINS_PDF_PAGE_TRANSLATION", "page translation string");
  
  */
  
  if(defined("KALINS_PDF_PAGE_TRANSLATION")){//if someone defined a new 'page' translation in wp-config, set its value
    $l['w_page'] = KALINS_PDF_PAGE_TRANSLATION;
    $objTcpdf->setLanguageArray($l);
  }
  
  //initialize document
  $objTcpdf->getAliasNbPages();

} catch (Exception $e) {
  $outputVar->status = "problem setting TCPDF options. Double check header titles and font size";
  die(json_encode($outputVar));
}

$objTcpdf->SetFont( 'Times', '', $fontSize );

$totalHTML = '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>' .kalins_pdf_global_shortcode_replace($headerTitle) .'</title>
  <meta name="description" content="' .kalins_pdf_global_shortcode_replace($headerSub) .'">
</head>
<body>';

$totalTXT = '';

try{
  if($titlePage != ""){
    $objTcpdf->AddPage();//create title page and start off our PDF file
    if($isSingle){
      $titlePage = kalins_pdf_page_shortcode_replace($titlePage, $result[0]);
    }else{
      $titlePage = kalins_pdf_global_shortcode_replace($titlePage);
    }
    $strHtml = wpautop($titlePage, true );
    $objTcpdf->writeHTML( $strHtml , true, 0, true, 0);
    
    $totalHTML = $totalHTML .$strHtml;
    $totalTXT = $totalTXT .$titlePage;
    
    if(!$oOptions->autoPageBreak && $includeTOC){//if we don't page-break between posts AND we're including table of contents, we need to break after the title page so TOC can be on second page
      $objTcpdf->AddPage();
    }
  }
} catch (Exception $e) {
  $outputVar->status = "problem creating title page.";
  die(json_encode($outputVar));
}

//record the location of the TOC so it can be injected at the proper place once all pages have been added
$TOCIndex = $objTcpdf->getNumPages() + 1;

//global $proBar;
//$proBar->setMessage('loading - this is a simulation ...');

try{
  $le = count($result);
  
  for($i = 0; $i < $le; $i++){
    
    if(!ini_get('safe_mode')){
      @set_time_limit(0);//set the timeout back to 0 so we can keep processing things that would normally choke with tons of pages and stuff (@ makes it ignore errors if this function is disabled)
    }
    
    $objPost = $result[$i];
        
    $content = $objPost->post_content;
    
    $post = $objPost;//set global post object so if other plugins run their shortcodes they'll have access to it. Not sure why query_posts doesn't take care of this
    query_posts('p=' .$post->ID);//for some reason this is also necessary so other plugins have access to values normally inside The Loop
    
    //each of our three video services have three ways of embedding: 1) the wordpress shortcode way 2)the official iframe and 3)the old method using an object to load flash
    //I'm doing my best to support all three
    if($oOptions->convertYoutube){
      $content = preg_replace("#\[embed\](.*)youtube.com/watch\?v=(.*)\[/embed]#", '<p><a href="http://www.youtube.com/watch?v=\\2">YouTube Video</a></p>', $content);
      $content = preg_replace("#<iframe(.*)youtube.com/embed/(.*)[\'\"] (.*)</iframe>#", '<p><a href="http://www.youtube.com/watch?v=\\2">YouTube Video</a></p>', $content);
      $content = preg_replace("#<object(.*)youtube.com/v/(.*)\"(.*)</object>#", '<p><a href="http://www.youtube.com/watch?v=\\2">YouTube Video</a></p>', $content);
    }
    
    if($oOptions->convertVimeo){
      $content = preg_replace("#\[embed\](.*)vimeo.com(.*)\[/embed]#", '<p><a href="http://vimeo.com\\2">Vimeo Video</a></p>', $content);
      $content = preg_replace("#<iframe(.*)player.vimeo.com/video/(.*)[\'\"] (.*)</iframe>#", '<p><a href="http://vimeo.com/\\2">Vimeo Video</a></p>', $content);
      $content = preg_replace("#<object(.*)vimeo.com/moogaloop.swf\?clip_id=(.*)&amp;server(.*)</object>#", '<p><a href="http://vimeo.com/\\2">Vimeo Video</a></p>', $content);
    }
    
    if($oOptions->convertTed){//TED Talks
      $content = preg_replace("#\[embed\](.*)ted.com(.*)\[/embed]#", '<p><a href="http://www.ted.com\\2">Ted Talk</a></p>', $content);
      $content = preg_replace("#<iframe(.*)ted.com/(.*)[\'\"] (.*)</iframe>#", '<p><a href="http://www.ted.com/\\2.html">Ted Talk</a></p>', $content);
      $content = preg_replace("#<object(.*)adKeys=talk=(.*);year=(.*)</object>#", '<p><a href="http://www.ted.com/talks/\\2.html">Ted Talk</a></p>', $content);
    }
    
    if(preg_match('/\[caption +[^\]]*\]/', $content)){//remove all captions surrounding images and whatnot since tcpdf can't interpret them (but leave the images in place)
      $content = preg_replace('/\[caption +[^\]]*\]/', '', $content);//replace all instances of the opening caption tag
      $content = preg_replace('/\[\/caption\]/', '', $content);//replace all instances of the closing caption tag
    }
    
    if($oOptions->runShortcodes){//if we're running shortcodes, run them
      $content = do_shortcode($content);
    }else{
      $content = strip_shortcodes($content);//if not, remove them
    }
    
    global $kalinsPDFRunning;
    $kalinsPDFRunning = true;
    
    if($oOptions->runFilters){//apply other plugin content filters if we're set to do that
      $content = apply_filters('the_content', $content);
    }
    
    if(!$oOptions->includeImages){
      //remove all image tags if we don't want images
      if(preg_match('/<img[^>]+./', $content)){
        $content = preg_replace('/<img[^>]+./', '', $content);
      }
    }
    
    /*if(preg_match('/< *blockquote *>/', $content)){//if we've got instances of <blockquote> in this content
      $content = preg_replace('/< *blockquote *>/', '<table border="0"><tr nobr="true"><td width="20">&nbsp;</td><td width="450"><pre>', $content);//replace it with a simple table
      $content = preg_replace('/< *\/ *blockquote *>/', '</pre></td></tr></table><br/>', $content);//now replace the closing tag
      
      //$content = preg_replace('/< *blockquote *>/', 'WTFFFFFFFFF--------', $content);//replace it with a simple table
      //$content = preg_replace('/< *\/ *blockquote *>/', 'AAAAAAAAAAAAAA-------', $content);//now replace the closing tag
    }*/
    
    if($objPost->post_type == "page"){//insert appropriate html before and after every page and post
      $content = $oOptions->beforePage .$content .$oOptions->afterPage;
    }else{
      $content = $oOptions->beforePost .$content .$oOptions->afterPost;
    }
    
    $content = kalins_pdf_page_shortcode_replace($content, $objPost);
    
    if($oOptions->autoPageBreak){
      // add a page
      $objTcpdf->AddPage();
    }
    
    if($includeTOC){//if we're including a TOC, add the bookmark. Pretty sweet that this still works if we're not adding new pages for each post
      $objTcpdf->Bookmark($objPost->post_title, 0, 0);
    }
  
    $strHtml = wpautop($content, true );
    $totalHTML = $totalHTML .$strHtml;
    $totalTXT = $totalTXT .$content;
    
    // output the HTML content
    $objTcpdf->writeHTML( $strHtml , true, 0, true, 0);
    
    //$proBar->increase();
  }
  
} catch (Exception $e) {
  $outputVar->status = "problem creating pages and posts. Perhaps there's a problem with one of the pages you've selected or with the before or after HTML.";
  die(json_encode($outputVar));
}

try{
  if($finalPage != ""){
    $objTcpdf->AddPage();//create final page in pdf
    $objTcpdf->SetFont( PDF_FONT_NAME_MAIN, '', $fontSize );
    
    if($isSingle){
      $finalPage = kalins_pdf_page_shortcode_replace($finalPage, $result[0]);
    }else{
      $finalPage = kalins_pdf_global_shortcode_replace($finalPage);
    }
    
    $strHtml = wpautop($finalPage, true );
    $totalHTML = $totalHTML .$strHtml;
    $totalTXT = $totalTXT .$finalPage;
        
    $objTcpdf->writeHTML( $strHtml , true, 0, true, 0);
  }
} catch (Exception $e) {
  $outputVar->status = "problem creating final page.";
  die(json_encode($outputVar));
}

try{
  
  if($includeTOC){
  
    // add a new page for TOC
    $objTcpdf->addTOCPage();
    
    // write the TOC title
    $objTcpdf->SetFont('times', 'B', 16);
    $objTcpdf->MultiCell(0, 0, 'Table Of Contents', 0, 'C', 0, 1, '', '', true, 0);
    $objTcpdf->Ln();
    
    $objTcpdf->SetFont('Times', '', $fontSize );
    
    //TODO: figure out if title page is more than a single page and if so, insert TOC at correct location instead of on page 2
    
    // add a simple Table Of Content at first page
    // (check the example n. 59 for the HTML version)
    $objTcpdf->addTOC($TOCIndex, 'courier', '.', 'INDEX');
    
    // end of TOC page
    $objTcpdf->endTOCPage();
  }
  
  //TODO: http://codex.wordpress.org/Function_Reference/wp_insert_attachment
  //see if you can enter this new file into the site's media library
  
  //create and save the proper document(s)
  if($isSingle){
    $objTcpdf->Output( $pdfDir .$filename .".pdf", 'F' );
  }  else {
    if( $oOptions->bCreatePDF){
      $objTcpdf->Output( $pdfDir .$filename .".pdf", 'F' );
    }
    
    if($oOptions->bCreateHTML){
      $totalHTML = $totalHTML .'</body>
  </html>';
      file_put_contents ( $pdfDir .$filename .".html" , $totalHTML );
    }
    
    if($oOptions->bCreateTXT){
      file_put_contents ( $pdfDir .$filename .".txt" , $totalTXT );
    }
  }
  
} catch (Exception $e) {
  $outputVar->status = "problem outputting the final PDF file.";
  die(json_encode($outputVar));
}

$outputVar->status = "success";//set success status for output to AJAX

if(!isset($skipReturn)){
  if($isSingle){//if this is called from a page/post we redirect so that user can download pdf directly
    header("Location: " .$pdfURL .$filename .".pdf");
    die();
  }else{
    die(json_encode($outputVar));//if it's called from the creation station admin panel we output the result object to AJAX
  }
}

?>