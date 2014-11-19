<?php
/*
error_reporting(E_ALL);
ini_set('display_errors', 1);  
ini_set('error_reporting', E_ALL);
*/

require_once('_functions/functions.php');



include('inc/header.php');
	
checkrights('admin', $USERDATA);

/*
if(gethostname() != $HOSTNAME_PRODUKTION) {
	$message = '<em>Wichtig:</em> Sie arbeiten nicht auf dem Produktiv-System. &Auml;nderungen an den Stammdaten haben keine Auswirkung auf die Produktiv-Abrechnung.';
	echo usermessage('error', $message);
} 
*/
echo '<h1>Todos bearbeiten</h1>';


$file = $TODO_FILE; //'../data/partner_configuration.csv';


$html = '';	
$html .= '<form id="form" action="edit_todos.php" method="POST">';
	
if(isset($_POST["todos"])) {
	$todos = $_POST["todos"];

	echo saveToCSV($todos, $file, true);
	
} else {

}



$html .= '<p><div class="container" style="float: left;"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>âœ“</span>Speichern</a></div><div style="clear: both;"></p>';
	
$html .= '<table id="accountmanagerconfig" border="1">';
		
$filecontent = readFromCSV($file, false);
		
foreach($filecontent as $k => $columns ) {

	$readonly = false;
	if($k == 0) {
		$readonly = ' readonly';
	}

	
	$html .= '<tr>';
		
	foreach($columns as $i => $cell) {

		if($k > 0 && $i == 1) {
			$html .= '<td style="vertical-align: top;"><p><textarea rows="12" class="column_'.$i.'" name="todos['.$k.']['.$i.']">'.str_replace('<br />', "\n", $cell).'</textarea></p></td>';	
		} else {
			$html .= '<td style="vertical-align: top;"><p><input type="text" class="column_'.$i.'" name="todos['.$k.']['.$i.']" value="'.($cell).'"'.$readonly.'></p></td>';	
		}
	}
	
	$html .= '</tr>';
}

$html .= '</table>';
$html .= '<p><div class="container" style="float: left;"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>âœ“</span>Speichern</a></div></p>';
$html .= '</form>';		
print($html);
	
			
?>