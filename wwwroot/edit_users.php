<?php

require_once('_functions/functions.php');

include('inc/header.php');

checkrights('admin', $USERDATA);
	
$html = '';	
$html .= '<h1>Benutzer bearbeiten</h1>';

$html .= '<P>Standard Password "Hallo1234" als Hash: "89df772a35c153d35d66a6211a09212c"</h1>';

$file = $USERS_FILE;



$html .= '<form id="form" action="edit_users.php" method="POST">';
	
if(isset($_POST["users"])) {
	$users = $_POST["users"];

	echo saveToCSV($users, $file, true, true);
	
} else {

}



$html .= '<p><div class="container" style="float: left;"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>✓</span>Speichern</a></div><div style="clear: both;"></p>';
	
$html .= '<table id="edit_users" border="1">';
		
$filecontent = readFromCSV($file, true, true);
		
foreach($filecontent as $k => $columns ) {

	$readonly = false;
	if($k == 0) {
		$readonly = ' readonly';
	}
	
	$html .= '<tr>';
		
	foreach($columns as $i => $cell) {

		$html .= '<td style="vertical-align: top;"><p><input type="text" class="column_'.$i.'" name="users['.$k.']['.$i.']" value="'.($cell).'"'.$readonly.'></p></td>';	
	}
	
	$html .= '</tr>';
}

$html .= '</table>';
$html .= '<p><div class="container" style="float: left;"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>✓</span>Speichern</a></div></p>';
$html .= '</form>';		
print($html);
	
			
?>