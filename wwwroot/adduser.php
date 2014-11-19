<?php

require_once('_functions/functions.php');

include('inc/header.php');

checkrights('admin', $USERDATA);


if(php_uname('n') != $HOSTNAME_PRODUKTION) {
	$message = '<em>Wichtig:</em> Sie arbeiten nicht auf dem Produktiv-System. &Auml;nderungen an den Stammdaten haben keine Auswirkung auf die Produktiv-Abrechnung.';
	echo usermessage('error', $message);
} 


$errors = false;

$username = false;
$email = false;
$passwort_plain = false;

//$password = false;

if(array_key_exists('user', $_POST)) {

	$uservalues = $_POST['user'];
	
	$username = $uservalues["username"];
	$email = $uservalues["email"];
	//$password = $uservalues["passwort"];
	//$password2 = $uservalues["passwort2"];
	$passwort_plain = $uservalues["passwort_plain"];
	


	if(!validate_username($username)) {
		echo usermessage('error', 'Der Benutzername ist zu kurz');
		$errors = true;
	}


	if ($passwort_plain) {
	
		if(!validate_password($passwort_plain)) {
			echo usermessage('error', 'Das Passwort ist zu unsicher');
			$errors = true;
		}
			
		$user_vorhanden = array();
		$passwort = md5($passwort_plain);


		$file = "_data/users.txt";
		
		require_once('_functions/base_functions.php');
		
		$users = readFromCSV($file, true);
	
		foreach($users as $i => $userdata) {
	
		      array_push ($user_vorhanden,$userdata['username']);
		}
	
		if (in_array($username,$user_vorhanden)) {
	
			echo usermessage('error', 'Username schon vorhanden <br> <a href="adduser.php">zur&uuml;ck</a>');
	
		} else {
	
			if(!$errors) {
				$line = "'".$username."';'".$passwort."';'".$email."';'user';'initialpassword';'-';'0'\n";

				$userdatei = fopen ("_data/users.txt","a");
				
				fwrite($userdatei, $line);
				
				fclose($userdatei);

				$subject = 'Ihr Benutzerkonto für ISBN Scan';
				$mailbody = ('Hallo '.$email.",\n\n".'Ihr Benutzerkonto wurde angelegt!'."\n\n".'Sie können sich ab sofort bei ISBN Scan einloggen'."\n\n".'Login: '.$username."\n".'Initialpasswort: '.$passwort_plain."\n\n");

//				$mailbody = "Hallo $email,\n\nIhr Benutzerkonto wurde angelegt!\n\nLogin: $username\nInitialpasswort: $passwort_plain\n\n";

				
				if(simplemail($email, $subject, $mailbody)) {
//					flo('mail sent!');
				} else {
//					flo('mail NOT sent!');

				}

				
				echo usermessage('success', 'Der Benutzer "'.$username.'" wurde erfolgreich angelegt.');
				die();				
			}
	
	      }
	} else {
	
		echo usermessage('error', 'Die Passwörter sind nicht identisch.');
	
	}
	
	
}

$html = '<h1>Benutzer anlegen</h1>';
$html .= '<p>Nachdem ein Benutzer mit einem Initialpasswort angelegt wurde muss der Benutzer nach dem ersten Login sein Passwort neu vergeben.</p>';
$html .= '<div id="form_container"><form id="form" action="adduser.php" method="post">';
$html .= '<p><label class="textinput">Username:</label><input type="text" size="24" maxlength="50" name="user[username]" value="'.$username.'"></p>';
$html .= '<p><label class="textinput">E-Mail:</label><input type="text" size="24" maxlength="50" name="user[email]" value="'.$email.'"></p>';
$html .= '<p><label class="textinput">Initialpasswort:</label><input type="text" size="24" maxlength="50" name="user[passwort_plain]" value="'.$passwort_plain.'"></p>';
//$html .= '<p><label class="textinput">Wiederholung:</label><input type="password" size="24" maxlength="50" name="user[passwort2]"></p>';
$html .= '<p><label class="textinput"><label><div class="container"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>✓</span>Benutzer anlegen</a></div><div class="container"></div></p>';
$html .= '</form>';


print($html);

include('inc/footer.php');

?>