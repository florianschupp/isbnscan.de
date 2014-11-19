<?php

require_once('_functions/functions.php');


include('inc/header.php');
	
echo '<h1>Benutzer anzeigen</h1>';

$file = $USERS_FILE; //'../data/users.txt';

$filters = array();
//$filters['partnertype'] = 'Kreditor';

$displaycols = array('username','email','status','roles','lastlogin','failedlogins');

$html = '<table id="partnerlist" border="1" style="width: 100%">';

$html .= '<thead>';
$html .= '<tr class="fistrow">';
$html .= '<th>Aktion</th>';

foreach($displaycols as $i => $displaycol) {
		$html .= '<th>'.$displaycol.'</th>';
}



$html .= '</tr>';
$html .= '</thead>';
$html .= '<tbody>';

$filecontent = readFromCSV($file, true);

foreach($filecontent as $k => $columns ) {
	
	$skiprow = false;

	foreach($filters as $colum_name => $filter_value) {
		if($columns[$colum_name] != $filter_value) {
			$skiprow = true;
		}
	}

	if(!$skiprow) {
		$html .= '<tr id="'.$k.'">';
		$html .= '<td><a href="edit_user.php?action=edit&id='.$columns['username'].'">Bearbeiten</a></td>';
		$sort = $columns['tier'].str_pad($columns['prio'], 8, '0', STR_PAD_LEFT);
		$columns['sort'] = $sort;
		foreach($columns as $i => $cell) {
			if(in_array($i, $displaycols)) {
				$html .= '<td>'.$cell.'</td>';
			}
		}

	}
	
	$html .= '</tr>';

}






$html .= '</table>';

	
print($html);
			
include('inc/footer.php');


	























		
?>
