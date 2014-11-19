<?php

require_once('_functions/functions.php');

include('inc/header.php');

$importfiles = array();
$importfiles = loadImportFiles();

checkrights('admin', $USERDATA);

$log = false;

if(isset($_GET["log"])) {
	$log = $_GET["log"];
} else {
	//die('Job not defined!');
	$log = 'errors';
}


$filenames = array();

$html = '';

if($log == 'errors') {
	
	$filenames['errors'] = $LOGFILES['errors']; 
	$filenames['useractions'] = $LOGFILES['useractions']; 

	
} else if($log == 'actions') {

	$filenames['actions'] = $LOGFILES['actions']; 
}
	


foreach($filenames as $key => $filename) {
	
	$fl = fopen($filename, "r");
	for($x_pos = 0, $ln = 0, $output = array(); fseek($fl, $x_pos, SEEK_END) !== -1; $x_pos--) {
	    $char = fgetc($fl);
	    if ($char === "\n") {
	        // analyse completed line $output[$ln] if need be
	        $ln++;
	        continue;
	        }
	    $output[$ln] = $char . ((array_key_exists($ln, $output)) ? $output[$ln] : '');
	    }
	fclose($fl);
	
	
	$html .= '<h2>'.$filename.'</h2>';
	$html .= '<p><div class="container" style="float: left;"><a href="deletelog.php?log='.$key.'" class="button red"><span>×</span>Logfile löschen</a></div><div style="clear: both;"></p>';
	
	$html .= '<pre class="code lines-'.(strlen(count($output))+1).'">';
	
	
	foreach($output as $i => $line) {
			
			$html .= '<code><i></i><span>'.$line.'</span><br/></code>';
		
	}
	$html .='</pre>';

	$html .= '<hr>';


}
echo $html;

include('inc/footer.php');

?>




