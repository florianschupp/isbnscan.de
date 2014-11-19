<?php


require_once('_functions/functions.php');

$action = $_GET['action'];

if($action == 'edit' OR $action == 'save') {

	$html = '<H1>Benutzer bearbeiten</H1>';	
	$html .= '<P>Standard Password "Hallo1234" als Hash: "89df772a35c153d35d66a6211a09212c"</h1>';

	$edit_id = $_GET['id'];
	$formaction = 'save';

} else if($action == 'new' OR $action == 'create') {

die('Not allowed here!');

	$edit_id = 'NEW';
	$formaction = 'create';

	$html = '<H1>Neuen Benutzer anlegen</H1>';	

} else {
	die('Invalid action');	
}


include('inc/header.php');

checkrights('user', $USERDATA);
	
if(php_uname('n') != $HOSTNAME_PRODUKTION) {
	$message = '<em>Wichtig:</em> Sie arbeiten nicht auf dem Produktiv-System. &Auml;nderungen an den Stammdaten haben keine Auswirkung auf die Produktiv-Abrechnung.';
	echo usermessage('error', $message);
} 

$file = $USERS_FILE;

$accounts = load_accountmanager($ACCOUNTMANAGER_FILE);
foreach($accounts as $abbrev => $account) {
	$am_ar[$abbrev] = $account['name'];
}

//Erst hier includen, weil das Array $am_ar übergeben werden muss.
require('_config/config_user.php');

$legende = '<table id="folders">';



foreach($user_structure as $colum => $config) {

		switch ($config['type']) {
		    case 'String':
		        $legende .= '<tr><td>'.$colum.'</td><td>'.$config['info'].'</td><td>Maximale Länge: '.$config['maxlength'].'</td></tr>';
		        break;
		    case 'Set':
		    
		        $legende .= '<tr><td>'.$colum.'</td><td>'.$config['info'].'</td><td>Wertebereich: '.implode(', ',$config['values']).'</td></tr>';
		        break;

		    case 'Emails':
		        $legende .= '<tr><td>'.$colum.'</td><td>'.$config['info'].'</td><td></td></tr>';
		        break;

		    default:
		        //echo 'undefined';
		        break;
		}
	
}
$legende .= '</table><br>';



$filecontent = readFromCSV($file, true, true);


$header_columns = array();
$existing_partners = array();
	
foreach($filecontent as $k => $columns ) {

	if($k == 0) {
		$header_columns = $columns; 
	}	

	if($action == 'edit') {

		if(trim($columns['username']) == $edit_id) {

			$columns['username'] = trim($columns['username']);

			$edit_partner = $columns;

			$line_id = $k;

	
		} else {
			continue;
		}

	} else if ($action == 'save') {

		if(trim($columns['username']) != $edit_id) {
	
			$existing_partners[$k] = $columns;
	
		} else {
			$insert_line_id = $k;
			continue;
		}

	} else if ($action == 'new') {
			$line_id = 'NEW';

	} else if ($action == 'create') {

		if($columns['username'] != $edit_id) {
	
			$existing_partners[$k] = $columns;
	
		} else {
			$insert_line_id = $k+1;
			continue;
		}
	}
}

if($action == 'edit' && count($edit_partner) == 0) {
	echo usermessage('error', 'Der Partner '.$edit_id.' existiert nicht!');
	die();
}


if(isset($_POST["partner"]) && ($action == 'save' OR $action == 'create')) {

	if ($action == 'create') {

		foreach($_POST["partner"]['NEW'] as $key => $value) {
			$_POST["partner"]['NEW'][$key] = trim($value);
		}

	}	
	
	$my_partner = $_POST["partner"];

	if(count($my_partner[key($my_partner)]) != count($user_structure)) {
		$message = 'edit_user.php: Fehler in der Datenstruktur beim Speichern von Partner '.$my_partner[key($my_partner)]['username'].'! Bitte Administrator benachrichtigen!';

		errorlog($message);		
		echo usermessage('error', $message);
		die();
	}

	if(key($my_partner) == $insert_line_id) {

		// Partner validiieren gegen Struktur

		$errors = array();
		$res = validateValues($my_partner[$insert_line_id], $user_structure, $existing_partners);			
		if($res) {
			$errors = $res;
		}
	
		if(count($errors) == 0) {
			$existing_partners[$insert_line_id] = $my_partner[$insert_line_id];
			ksort($existing_partners);


			// Write the contents back to the file
			echo saveToCSV($existing_partners, $file, true, true);

			echo '<p><div class="container" style="float: left;"><a href="edit_user.php?action=edit&id='.$edit_id.'" class="button"><span>✓</span>weiter</a></div><div style="clear: both;"></p>';



			die();

		} else {

			foreach($errors as $field => $text) {
				echo usermessage('error', $text);
			}

			echo printEditForm($my_partner[$insert_line_id], $formaction, $insert_line_id, $header_columns, $user_structure, $edit_id);
			die('ok');
		}


	} else if(key($my_partner) == 'NEW') {

		$errors = array();
		$res = validateValues($my_partner['NEW'], $user_structure, $existing_partners);	

		
		if($res) {
			$errors = $res;
		}
	
		if(count($errors) == 0) {

			$existing_partners[] = $my_partner['NEW'];
	
			echo saveToCSV($existing_partners, $file, true, true);

			echo '<p><div class="container" style="float: left;"><a href="edit_user.php" class="button"><span>✓</span>Benutzer bearbeiten</a></div><div style="clear: both;"></p>';

			die();
		} else {

			foreach($errors as $field => $text) {
				echo usermessage('error', $text);
			}

			echo printEditForm($my_partner['NEW'], $formaction, 'NEW', $header_columns, $user_structure, $edit_id);
			die('');
		}		

	}

} else {
	print($html);
	echo printEditForm($edit_partner, $formaction, $line_id, $header_columns, $user_structure, $edit_id);
}



		
include('inc/footer.php');




function printEditForm($edit_partner, $formaction, $line_id, $header_columns, $user_structure, $edit_id) {

	$html .= '<form id="form" action="edit_user.php?action='.$formaction.'&id='.$edit_id.'" method="POST">';	

	$html .= '<table id="partnerconfig">';
	
	foreach($header_columns as $key => $label) {
	
		$html .= '<tr>';
		$html .= '<td><p>'.$label.'</p></td>';
	
	
		switch ($user_structure[$key]['type']) {
			case 'String':

				$html .= '<td><p>';
				//$html .= '<input type="text" maxlength="'.$user_structure[$key]['maxlength'].'" class="column_'.$i.'" name="partner['.$line_id.']['.$key.']" value="'.$edit_partner[$key].'" style="width: '.min(450,$user_structure[$key]['maxlength']*8+30).'px;">';

				$html .= '<input type="text" maxlength="'.$user_structure[$key]['maxlength'].'" class="column_'.$i.'" name="partner['.$line_id.']['.$key.']" value="'.trim($edit_partner[$key]).'" style="width: 450px;">';

				$html .= '</p></td>';

				$html .= '<td style="font-size: 10px; vertical-align: top;">'.$user_structure[$key]['info'].'<br>Maximale Länge: '.$user_structure[$key]['maxlength'].'</td>';
				break;

			case 'Set':
				$html .= '<td><p>';

				$html .= '    <div class="dropdown dropdown-dark" style="width:240px; height: 33px; margin-left: 6px;">';
				$html .= '      <select name="partner['.$line_id.']['.$key.']" class="dropdown-select">';

				foreach($user_structure[$key]['values'] as $id => $label) {

						if($edit_partner[$key] == $id) {
							$selected = 'selected';
						} else {
							$selected = false;
						}
						$html .= '<option '.$selected.' value="'.$id.'">'.$label.'</option>';	
				}
				
				$html .= '      </select>';
				$html .= '    </div>';

				//$html .= '<input type="text" class="column_'.$i.'" name="partner['.$line_id.']['.$key.']" value="'.$edit_partner[$key].'" style="width: 40em;">';

				$html .= '</p></td>';
		    
				$html .= '<td style="font-size: 10px; vertical-align: top;">'.$user_structure[$key]['info'].'<br>Wertebereich: '.implode(', ',$user_structure[$key]['values']).'</td>';
				break;

			case 'Emails':

				$html .= '<td><p>';
				$html .= '<input type="text" class="column_'.$i.'" name="partner['.$line_id.']['.$key.']" value="'.$edit_partner[$key].'" style="width: 450px;">';
				$html .= '</p></td>';

				$html .= '<td style="font-size: 10px; vertical-align: top;">'.$user_structure[$key]['info'].'<br></td>';
				break;
			default:
			        //echo 'undefined';
			        break;
		}
		$html .= '</tr>';
	}

	$html .= '</table>';

	$html .= '<p><div class="container" style="float: left;"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>✓</span>User speichern</a></div></p>';
	$html .= '</form>';	

	return $html;
}




function validateValues($entry, $user_structure, $existing_partners) {

	$errors = array();
	
	foreach($entry as $field => $value) {

		if(array_key_exists($field, $user_structure)) {		

			if(array_key_exists('unique', $user_structure[$field])) {

				foreach($existing_partners as $i => $existing_partner) {
					if($existing_partner[$field] == $value) {
						$errors[$field] = 'Das Feld "'.$field.'" muss eindeutig sein. Der Wert "'.$value.'" existiert bereits!';
					}
				}
			}
				
			switch ($user_structure[$field]['type']) {
			    case 'String':

				if(array_key_exists('minlength', $user_structure[$field]) && (strlen(trim($value)) < $user_structure[$field]['minlength'])) {
					$errors[$field] = 'Der Wert "'.$value.'" in Feld '.$field.' ist zu kurz (Minimale Länge: '.$user_structure[$field]['minlength'].')';
				}

				if(array_key_exists('required', $user_structure[$field]) && (strlen(trim($value)) == 0)) {
					$errors[$field] = 'Der Wert "'.$field.'" in ein Pflichtfeld';
				}

				if(strlen($value) > $user_structure[$field]['maxlength']) {
					$errors[$field] = 'Der Wert "'.$value.'" in Feld '.$field.' ist zu lang (Maximale Länge: '.$user_structure[$field]['maxlength'].')';
				}


			       break;
			    case 'Set':

					if(!array_key_exists('values', $user_structure[$field])) {
							echo usermessage('error', 'Konfigurationsfehler: Feld "'.$field.'" (Typ "Set") ist nicht ausreichend definiert, "values" nicht gesetzt.');				
					} else {
						if(!array_key_exists($value, $user_structure[$field]['values'])) {
							$errors[$field] = 'Wert '.$value.' ist nicht erlaubt. (Zulässige Werte: '.implode(', ', $user_structure[$field]['values']).')';
						} else {
						}
					}

			        break;
	
			    default:
			        //echo 'undefined';
			        break;
			}
		} else {
			$errors[$field] = 'Feld '.$field.' ist nicht definiert';
		}
	}

	

	if(count($errors) == 0) {
		return false;
	} else {
		return $errors;
	}


}


?>
