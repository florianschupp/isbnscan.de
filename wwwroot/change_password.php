<?php

require_once('_functions/functions.php');

include('inc/header.php');
	
checkrights('user', $USERDATA);

$html = '';

$file = $USERS_FILE;

	
if(isset($_POST["user"])) {
	$uservalues = $_POST["user"];
	
	//flo($uservalues);

	$filecontent = readFromCSV($file, true);

	if($uservalues['passwort'] == $uservalues['passwort2'] && validate_password($uservalues['passwort'])) { // Do the Update

		//flo($filecontent);

		foreach($filecontent as $k => $columns) {

			if($columns['username'] == $_SESSION['username']) {

				//flo($columns);

				$columns['passwordhash'] = passwordhash($uservalues['passwort']);
				$columns['email'] = addslashes($uservalues['email']);

				if($columns['status'] == 'initialpassword') {
					$columns['status'] = 'active';		
					
					$html .= usermessage('success', 'Ihr Konto ist nun aktiv<br><br><a href="send.php">Hier geht es weiter</a>');		
				}

				$filecontent[$k] = $columns;

			}
		}

		echo saveToCSV($filecontent, $file, true);
//		flo($filecontent);

	} else { // We have errors!

		if($uservalues['passwort'] != $uservalues['passwort2']) {
			echo usermessage('error', 'Passwort und Wiederholung stimmen nicht &uuml;berein!');
		} else if(!validate_password($uservalues['passwort'])) {
			echo usermessage('error', 'Passwort zu unsicher!');
		}

		foreach($filecontent as $k => $columns ) {

			if($columns['username'] == $_SESSION['username']) {

				$html = '<h1>Passwort für "'.$columns['username'].'" ändern</h1>';
				$html .= '<div id="form_container"><form id="form" action="change_password.php" method="post">';
				$html .= '<div id="form_container"><form id="form" action="adduser_process.php" method="post">';
				$html .= '<p><label class="textinput">Username:</label>'.$columns['username'].'</p>';
				$html .= '<p><label class="textinput">E-Mail:</label><input type="text" size="24" maxlength="50" name="user[email]" value="'.$columns['email'].'"></p>';
				$html .= '<p><label class="textinput">Passwort:</label><input type="password" size="24" maxlength="50" name="user[passwort]"></p>';
				$html .= '<p><label class="textinput">Passwort Wiederholung:</label><input type="password" size="24" maxlength="50" name="user[passwort2]"></p>';
				$html .= '<p><label class="textinput"><label><div class="container"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>✓</span>Passwort speichern</a></div><div class="container"></div></p>';
				$html .= '</form>';

			}
		}

	}
		

	
} else {



	$filecontent = readFromCSV($file, true);
		
	foreach($filecontent as $k => $columns ) {
		
		if($columns['username'] == $_SESSION['username']) {

//			flo($columns);

			
			if($columns['status'] == 'initialpassword') {
				
				$html = '<h1>Initialpasswort für "'.$columns['username'].'" ändern</h1>';
				
				$html .= usermessage('info', 'Bitte das Passwort aus Sicherheitsgründen erneut festlegen, die Verschlüsselung wurde verbessert.</a>');		

				
			} else {
					
				$html = '<h1>Passwort für "'.$columns['username'].'" ändern</h1>';
			}
			
			$html .= '<div id="form_container"><form id="form" action="change_password.php" method="post">';
			$html .= '<div id="form_container"><form id="form" action="adduser_process.php" method="post">';
//			$html .= '<p><label class="textinput">Username:</label>'.$columns['username'].'</p>';
			$html .= '<p><label class="textinput">E-Mail:</label><input type="text" size="24" maxlength="50" name="user[email]" value="'.$columns['email'].'"></p>';
			$html .= '<p><label class="textinput">Passwort:</label><input type="password" size="24" maxlength="50" name="user[passwort]" value="" autocomplete="off"></p>';
			$html .= '<p><label class="textinput">Passwort Wiederholung:</label><input type="password" size="24" maxlength="50" name="user[passwort2]" value="" autocomplete="off"> </p>';
			$html .= '<p><label class="textinput"><label><div class="container"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>✓</span>Passwort speichern</a></div><div class="container"></div></p>';
			$html .= '</form>';

		}
	}
}


		
print($html);
	

include('inc/footer.php');


			
?>