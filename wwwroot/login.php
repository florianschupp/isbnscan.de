<?php
require_once('_functions/base_functions.php');

session_start();

$username = $_POST["username"];
$passwort = $_POST["password"];


if(array_key_exists('message', $_GET)) {
	$message = $_GET["message"];
	echo usermessage('info', 'Ihre Session ist abgelaufen, bitte melden Sie sich erneut an');
} else {
	$message = false;
}



$client_ip = '';

if ( isset($_SERVER["REMOTE_ADDR"]) )    {
    $client_ip = $_SERVER["REMOTE_ADDR"];
} else if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) )    {
    $client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
} else if ( isset($_SERVER["HTTP_CLIENT_IP"]) )    {
    $client_ip = $_SERVER["HTTP_CLIENT_IP"];
} 

if($username OR $passwort) {

	$crypt_passwort = passwordhash($passwort);
	$md5_passwort = md5($passwort);

	
	$log=0;
	$file = "_data/users.txt";
	
	
	$users = readFromCSV($file, true);

	foreach($users as $i => $userdata) {

		if(strlen(trim($userdata['passwordhash'])) == 32) {
			$passwordhash = $md5_passwort;
		} else {
			$passwordhash = $crypt_passwort;
		}

		if ($userdata['username'] == $username && $passwordhash != trim($userdata['passwordhash'])) { 


			$html = '';
			$users[$i]['failedlogins']++;
			
			if($users[$i]['failedlogins'] == $max_failed_logins+1) { // Send Infomail only once
				
				 simplemail($users[$i]['email'], 'Warnung: Ihr Benutzerkonto wurde gesperrt', "Hallo ".$users[$i]['username']."\n\nIhr Benutzerkonto wurde aufgrund von ".$users[$i]['failedlogins']." fehlgeschlagenen Anmeldeversuchen gesperrt. Zur Aktivierung wenden Sie sich bitte an den Administrator.");

				 simplemail($admin_email, 'Warnung: Benutzerkonto wurde gesperrt', 'Warnung: Benutzer '.$users[$i]['username'].' wurde gesperrt');

				 echo usermessage('error', 'Das Benutzerkonto wurde gesperrt.');
				 //die();
				
			} else if($users[$i]['failedlogins'] > $max_failed_logins+1) { // Send Infomail only once

				 echo usermessage('error', 'Das Benutzerkonto wurde gesperrt.');
			}
			
			$html = saveToCSV($users, $file);
			//echo $html;	
			
		}
		
		if ($userdata['username'] == $username && $passwordhash == trim($userdata['passwordhash'])) { // Let's log the user in


			if($userdata['failedlogins'] > $max_failed_logins) {
			
				echo usermessage('error', 'Ihr Zugang wurde aus Sicherheitsgründen deaktiviert, die maximale Anzahl fehlerhafter Logins wurde überschritten.<br><br>Bitte wenden Sie sich an den Administrator!');
				//die();
				
			} else {
		
				$_SESSION['username'] = $username;
				$_SESSION['starttime'] = time();
				$_SESSION['last_access'] = time();
		
				if(strlen($_SESSION['last_url']) > 0) {
									
					$returnlink = $_SESSION['last_url'];
	
				} else {
					$returnlink = 'home.php';
				}

				$users[$i]['lastlogin'] = time();
				$users[$i]['failedlogins'] = 0;
				
				$html = saveToCSV($users, $file);

				$log = 1;

				session_write_close(); 


				header('Location: '.$returnlink);
				die();			
				
			}
			
			

		} else {

			
		}
	}

	


	if ($log==0) {

		loginform_new('Fehler', 'Passwort und / oder Benutzername sind falsch. Ihre IP Adresse wurde aufgezeichnet.');

		useractionlog('Fehlgeschlagener Loginversuch mit Username "'.$username.'" (IP-Adresse: '.$client_ip.' Host: '.gethostbyaddr($client_ip));

		//include('inc/footer.php');
		die();
	}
	
	?>
	
</body>
</html>

<?php

} else {

	if($_SESSION && $_SESSION['username']) {

		$session_idletime = time()-$_SESSION['last_access'];
		$session_age = time()-$_SESSION['starttime'];
	
		if($session_idletime > $SESSION_IDLE_LIFETIME) {
			//print_r($session_idletime);
			echo loginform_new();
			die();		
		} else if($session_age > $SESSION_LIFETIME) {
			//print_r($session_idletime);
			echo loginform_new();
			die();		
		} else {
			//$USERDATA = loaduser($USERS_FILE);
		}

	}
	
	loginform_new();
}






function loginform_new($error_title = false, $error_text = false) {


?>

<!DOCTYPE html>
<!--[if lt IE 7]> <html class="lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]> <html class="lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]> <html class="lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>ISBN Scan - Login</title>


  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="css/messages.css">

  <!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

  <link rel="shortcut icon" href="favicon.ico">

	<noscript><div class="alert alert-error"><a class="close" data-dismiss="alert">Ã—</a><strong>Fehler: Javascript muss aktiviert sein.</strong></div><div class="success-message"></noscript>
	<script type="text/javascript"><!--
	if(document.cookie.indexOf('PHPSESSID')!=-1)
	   document.getElementById('form').style.display='';
	else
	   document.write('<div class="alert alert-error"><a class="close" data-dismiss="alert">Ã—</a><strong>Fehler: Cookies mÃ¼ssen aktiviert sein.</strong></div><div class="success-message">');
	--></script>

</head>
<body>

<?php
$ua = browser_info();
if(array_key_exists('msie', $ua)) {

	echo usermessage('error', '<b>Wichtig:</b> Ihr Browser wird nicht unterst&uuml;tzt! Bitte verwenden Sie Firefox oder Safari, ansonsten kann es zu Darstellungsproblemen kommen!');

}
?>

<div id="wrapper">

	<form name="login-form" class="login-form" action="login.php" method="post">
	
		<div class="header">

		<h1>ISBN Scan - Login</h1>
		<span>Bitte geben Sie Ihre Zugangsdaten ein. Ihre IP Adresse wird aufgezeichnet.</span>
		</div>
	
		<div class="content">
		<input name="username" type="text" class="input username" placeholder="Benutzername" />
		<div class="user-icon"></div>
		<input name="password" type="password" class="input password" placeholder="Passwort" />
		<div class="pass-icon"></div>		
		</div>

<?
if($error_title AND $error_text) {
?>
		<div class="error">
		<h1><?php echo $error_title; ?></h1>
		<span><?php echo $error_text; ?></span>
		</div>	
<?
}
?>

		
		
		<div class="footer">
		<input type="submit" name="submit" value="Login" class="button" />
		</div>
	
	</form>
</div>
<div class="gradient"></div>

</body>
</html>


<?
}

?>