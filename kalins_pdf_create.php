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
	
	require_once('tcpdf/config/lang/eng.php');
	require_once('tcpdf/tcpdf.php');
} catch (Exception $e) {
	$outputVar->status = "problem loading wp-config or TCPDF library.";
	echo json_encode($outputVar);
}

kalinsPDF_createPDFDir();

global $wpdb, $post;

//$uploads = wp_upload_dir();
//$uploadDir = $uploads['basedir'];
//$uploadURL = $uploads['baseurl'];

$adminOptions = kalins_pdf_get_admin_options();

if($isSingle){
	if(!isset($pageIDs)){
		$pageIDs = $_GET["singlepost"];
	}
	
	$singleID = substr($pageIDs, 3);

	$pdfDir = KALINS_PDF_SINGLES_DIR;
	//$pdfURL = $uploadURL .'/kalins-pdf/singles/';
	$pdfURL = KALINS_PDF_SINGLES_URL;
	
	if($adminOptions["filenameByTitle"] == "true"){
		
		$singlePost = "";
		
		//echo "My page ids " .$pageIDs;
		
		if(substr($pageIDs, 0, 2) == "po"){
			$singlePost = get_post($singleID);
		}else{
			$singlePost = get_page($singleID);
		}
		
		$fileName = $singlePost->post_name .'.pdf'; 
		
	}else{
		$fileName = $singleID .'.pdf';
	}
	
	if(file_exists($pdfDir .$fileName)){//if the file already exists, simply redirect to that file and we're done
		if(!isset($skipReturn)){
			header("Location: " .$pdfURL .$fileName);
		}
		return;
	}else{
		$outputVar->fileName = $fileName;
		$outputVar->date = date("Y-m-d H:i:s", time());
		
		$adminOptions = kalins_pdf_get_admin_options();//for individual pages/posts we grab all the PDF options from the options page instead of the POST
		
		$titlePage = $adminOptions["titlePage"];
		$finalPage = $adminOptions["finalPage"];
		$beforePage = $adminOptions["beforePage"];
		$beforePost = $adminOptions["beforePost"];
		$afterPage = $adminOptions["afterPage"];
		$afterPost = $adminOptions["afterPost"];
		$headerTitle = $adminOptions["headerTitle"];
		$headerSub = $adminOptions["headerSub"];
		$includeImages = $adminOptions["includeImages"];
		$runShortcodes = $adminOptions["runShortcodes"];
		$runFilters = $adminOptions["runFilters"];
		$convertYoutube = $adminOptions["convertYoutube"];
		$convertVimeo = $adminOptions["convertVimeo"];
		$convertTed = $adminOptions["convertTed"];
		//$includeTables = $adminOptions["includeTables"];
		$fontSize = $adminOptions["fontSize"];
		
		$autoPageBreak = "true";
		$includeTOC = "false";//singles don't get no Table of contents
	}
}else{
	try{
		//$pdfDir = $uploadDir .'/kalins-pdf/';
		
		$pdfDir = KALINS_PDF_DIR;
		
		//echo $pdfDir ." pdf dir!!" .KALINS_PDF_DIR;
		
		if($_POST["fileNameCont"] != ""){
			$fileName = kalins_pdf_global_shortcode_replace($_POST["fileNameCont"]) .".pdf";
		}else{
			$fileName = time() .".pdf";
		}
		
		//$documentType = "html";
		$pageIDs = stripslashes($_POST["pageIDs"]);
		$titlePage = stripslashes($_POST['titlePage']);
		$finalPage = stripslashes($_POST['finalPage']);
		$beforePage = stripslashes($_POST['beforePage']);
		$beforePost = stripslashes($_POST['beforePost']);
		$afterPage = stripslashes($_POST['afterPage']);
		$afterPost = stripslashes($_POST['afterPost']);
		$headerTitle = stripslashes($_POST['headerTitle']);
		$headerSub = stripslashes($_POST['headerSub']);
		//$headerKeyWords = "list, of, keywords,";
		$includeImages = stripslashes($_POST['includeImages']);
		$runShortcodes = stripslashes($_POST["runShortcodes"]);
		$runFilters = stripslashes($_POST["runFilters"]);
		$convertYoutube = stripslashes($_POST["convertYoutube"]);
		$convertVimeo = stripslashes($_POST["convertVimeo"]);
		$convertTed = stripslashes($_POST["convertTed"]);
		//$includeTables = stripslashes($_POST['includeTables']);
		$autoPageBreak = stripslashes($_POST["autoPageBreak"]);
		$includeTOC = stripslashes($_POST["includeTOC"]);
		$fontSize = (int) $_POST['fontSize'];
		
		$kalinsPDFToolOptions = array();//collect our passed in values so we can save them for next time
			
		$kalinsPDFToolOptions["headerTitle"] = $headerTitle;
		$kalinsPDFToolOptions["headerSub"] = $headerSub;
		$kalinsPDFToolOptions["filename"] = $_POST["fileNameCont"];
		$kalinsPDFToolOptions["includeImages"] = $includeImages;
		$kalinsPDFToolOptions["runShortcodes"] = $runShortcodes;
		$kalinsPDFToolOptions["runFilters"] = $runFilters;
		$kalinsPDFToolOptions["convertYoutube"] = $convertYoutube;
		$kalinsPDFToolOptions["convertVimeo"] = $convertVimeo;
		$kalinsPDFToolOptions["convertTed"] = $convertTed;
		
		//$convertVimeo
		//$kalinsPDFToolOptions["includeTables"] = $includeTables;
		$kalinsPDFToolOptions["beforePage"] = $beforePage;
		$kalinsPDFToolOptions["beforePost"] = $beforePost;
		$kalinsPDFToolOptions["afterPage"] = $afterPage;
		$kalinsPDFToolOptions["afterPost"] = $afterPost;
		$kalinsPDFToolOptions["titlePage"] = $titlePage;
		$kalinsPDFToolOptions["finalPage"] = $finalPage;
		$kalinsPDFToolOptions["fontSize"] = $fontSize;
		$kalinsPDFToolOptions["autoPageBreak"] = $autoPageBreak;
		$kalinsPDFToolOptions["includeTOC"] = $includeTOC;
		
		update_option(KALINS_PDF_TOOL_OPTIONS_NAME, $kalinsPDFToolOptions);//save options to database
	} catch (Exception $e) {
		$outputVar->status = "problem setting options. Be sure the text you have entered is compatible or try resetting to defaults.";
		echo json_encode($outputVar);
	}	
	
	if(file_exists($pdfDir .$fileName)){//if the file already exists, echo an error and quit
		$outputVar->status = "file already exists.";
		echo json_encode($outputVar);
		return;
	}else{
		$outputVar->fileName = $fileName;
		$outputVar->date = date("Y-m-d H:i:s", time());
	}
}

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
	echo json_encode($outputVar);
	return;
}

try{
	// create new PDF document
	$objTcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);
	// set document information
	$objTcpdf->SetCreator(PDF_CREATOR);
	
	if($isSingle){
		$objTcpdf->SetTitle( kalins_pdf_page_shortcode_replace($headerTitle, $result[0]) );// set default header data
		$objTcpdf->SetHeaderData(null, null, kalins_pdf_page_shortcode_replace($headerTitle, $result[0]), kalins_pdf_page_shortcode_replace($headerSub, $result[0]) );
	}else{
		$objTcpdf->SetTitle( kalins_pdf_global_shortcode_replace($headerTitle, $isSingle) );// set default header data
		$objTcpdf->SetHeaderData(null, null, kalins_pdf_global_shortcode_replace($headerTitle), kalins_pdf_global_shortcode_replace($headerSub) );
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
	}
	
	$objTcpdf->setLanguageArray($l); 
	
	//initialize document
	$objTcpdf->getAliasNbPages();

} catch (Exception $e) {
	$outputVar->status = "problem setting TCPDF options. Double check header titles and font size";
	echo json_encode($outputVar);
	return;
}

$objTcpdf->SetFont( 'Times', '', $fontSize );

try{
	if($titlePage != ""){
		$objTcpdf->AddPage();//create title page and start off our PDF file
		//$objTcpdf->SetFont( PDF_FONT_NAME_MAIN, '', $fontSize );
		if($isSingle){
			$titlePage = kalins_pdf_page_shortcode_replace($titlePage, $result[0]);
		}else{
			$titlePage = kalins_pdf_global_shortcode_replace($titlePage);
		}
		$strHtml = wpautop($titlePage, true );
		$objTcpdf->writeHTML( $strHtml , true, 0, true, 0);
		
		if($autoPageBreak != "true" && $includeTOC == "true"){//if we don't page-break between posts AND we're including table of contents, we need to break after the title page so TOC can be on second page
			$objTcpdf->AddPage();
		}
	}
} catch (Exception $e) {
	$outputVar->status = "problem creating title page.";
	echo json_encode($outputVar);
	return;
}

//global $proBar;
//$proBar->setMessage('loading - this is a simulation ...');

try{
	$le = count($result);
	
	
	
	for($i = 0; $i < $le; $i++){
		
		if(!ini_get('safe_mode')){
			set_time_limit(0);//set the timeout back to 0 so we can keep processing things that would normally choke with tons of pages and stuff
		}
		
		$objPost = $result[$i];
		
		//global $post;
		
		$content = $objPost->post_content;
		
		$post = $objPost;//set global post object so if other plugins run their shortcodes they'll have access to it. Not sure why query_posts doesn't take care of this
		query_posts('p=' .$post->ID);//for some reason this is also necessary so other plugins have access to values normally inside The Loop
		
		if($runShortcodes == "true"){//if we're running shortcodes, run them
			$content = do_shortcode($content);
		}else{
			$content = strip_shortcodes($content);//if not, remove them
		}
		
		global $kalinsPDFRunning;
		$kalinsPDFRunning = true;
		
		if($runFilters == "true"){//apply other plugin content filters if we're set to do that
			$content = apply_filters('the_content', $content);
		}
		
		if($convertYoutube == "true"){
			$content = preg_replace("#<object(.*)youtube.com/v/(.*)\"(.*)</object>#", '<p><a href="http://www.youtube.com/watch?v=\\2">YouTube Video</a></p>', $content);
			$content = preg_replace("#<iframe(.*)http://www.youtube.com/embed/(.*)\"(.*)</iframe>#", '<p><a href="http://www.youtube.com/watch?v=\\2">YouTube Video</a></p>', $content);
		}
		
		if($convertVimeo == "true"){
			$content = preg_replace("#<object(.*)vimeo.com/moogaloop.swf\?clip_id=(.*)&amp;server(.*)</object>#", '<p><a href="http://vimeo.com/\\2">Vimeo Video</a></p>', $content);
			$content = preg_replace("#<iframe(.*)http://player.vimeo.com/video/(.*)\" (.*)</iframe>#", '<p><a href="http://vimeo.com/\\2">Vimeo Video</a></p>', $content);
		}
		
		if($convertTed == "true"){//TED Talks
			$content = preg_replace("#<object(.*)adKeys=talk=(.*);year=(.*)</object>#", '<p><a href="http://www.ted.com/talks/\\2.html">Ted Talk</a></p>', $content);
		}
		
		if(preg_match('/\[caption +[^\]]*\]/', $content)){//remove all captions surrounding images and whatnot since tcpdf can't interpret them (but leave the images in place)
			$content = preg_replace('/\[caption +[^\]]*\]/', '', $content);//replace all instances of the opening caption tag
			$content = preg_replace('/\[\/caption\]/', '', $content);//replace all instances of the closing caption tag
		}
		
		if($includeImages != "true"){
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
			$content = $beforePage .$content .$afterPage;
		}else{
			$content = $beforePost .$content .$afterPost;
		}
		
		$content = kalins_pdf_page_shortcode_replace($content, $objPost);
		
		if($autoPageBreak == "true"){
			// add a page
			$objTcpdf->AddPage();
		}
		
		if($includeTOC == "true"){//if we're including a TOC, add the bookmark. Pretty sweet that this still works if we're not adding new pages for each post
			$objTcpdf->Bookmark($objPost->post_title, 0, 0);
			//$objTcpdf->Cell(0, 10, '', 0, 1, 'L');
		}
		
		// set font
		//$objTcpdf->SetFont( PDF_FONT_NAME_MAIN, '', $fontSize );
		
		//$content = apply_filters('the_content', $content);
	
		$strHtml = wpautop($content, true );
		
		// output the HTML content
		$objTcpdf->writeHTML( $strHtml , true, 0, true, 0);
		
		//$proBar->increase();
	}
	
} catch (Exception $e) {
	$outputVar->status = "problem creating pages and posts. Perhaps there's a problem with one of the pages you've selected or with the before or after HTML.";
	echo json_encode($outputVar);
	return;
}

try{
	if($finalPage != ""){
		$objTcpdf->AddPage();//create final page in pdf
		$objTcpdf->SetFont( PDF_FONT_NAME_MAIN, '', $fontSize );
		//$finalPage = kalins_pdf_global_shortcode_replace($finalPage, $isSingle);
		
		if($isSingle){
			$finalPage = kalins_pdf_page_shortcode_replace($finalPage, $result[0]);
		}else{
			$finalPage = kalins_pdf_global_shortcode_replace($finalPage);
		}
		
		$strHtml = wpautop($finalPage, true );
		$objTcpdf->writeHTML( $strHtml , true, 0, true, 0);
	}
} catch (Exception $e) {
	$outputVar->status = "problem creating final page.";
	echo json_encode($outputVar);
	return;
}

try{
	
	if($includeTOC == "true"){
	
		// add a new page for TOC
		$objTcpdf->addTOCPage();
		
		// write the TOC title
		$objTcpdf->SetFont('times', 'B', 16);
		$objTcpdf->MultiCell(0, 0, 'Table Of Contents', 0, 'C', 0, 1, '', '', true, 0);
		$objTcpdf->Ln();
		
		$objTcpdf->SetFont('Times', '', $fontSize );
		
		// add a simple Table Of Content at first page
		// (check the example n. 59 for the HTML version)
		$objTcpdf->addTOC(2, 'courier', '.', 'INDEX');
		
		// end of TOC page
		$objTcpdf->endTOCPage();
	}
	
	
	
	
	//create and save the PDF document
	$objTcpdf->Output( $pdfDir .$fileName, 'F' );
} catch (Exception $e) {
	$outputVar->status = "problem outputting the final PDF file.";
	echo json_encode($outputVar);
	return;
}

$outputVar->status = "success";//set success status for output to AJAX

if(!isset($skipReturn)){
	if($isSingle){//if this is called from a page/post we redirect so that user can download pdf directly
		header("Location: " .$pdfURL .$fileName);
	}else{
		echo json_encode($outputVar);//if it's called from the creation station admin panel we output the result object to AJAX
	}
}

?>