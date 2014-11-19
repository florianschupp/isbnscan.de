<?php
//ini_set('session.save_path','/srv/www/000_PHPSESSIONS');

error_reporting(E_ALL ^ E_NOTICE);
ini_set('error_reporting', E_ERROR);
//ini_set('error_reporting', E_ALL);

ini_set('display_errors', true);  

$LOGFILES['useractions'] = '_logs/useractions.log';
$LOGFILES['actions'] = '_logs/actions.log';
$LOGFILES['errors'] = '_logs/errors.log';

$SESSION_IDLE_LIFETIME = 3600; // Sekunden
$SESSION_LIFETIME = 60*60*12; // Sekunden

$default_from_email = 'betrieb@isbnscan.de';
$admin_email = 'betrieb@isbnscan.de';
$max_failed_logins = 5;

function simplemail($to, $subject, $message) {

	global $default_from_email; 
	require_once('classes/class.phpmailer.php');
	
	$SEND_EMAILS = true; 
	
	$phpmail = new PHPMailer;
	
	//$phpmail->IsSMTP();
	$phpmail->IsSendmail();
	  
    /*
    // Set mailer to use SMTP
	$phpmail->Host = 'mail.former03.de';  // Specify main and backup server
	$phpmail->SMTPAuth = true;                               // Enable SMTP authentication
	$phpmail->Username = 'info@mailfactory.pubbles.de';                            // SMTP username
	$phpmail->Password = '****';                           // SMTP password	
	$phpmail->SMTPSecure = 'tls';      
	*/	
	                      // Enable encryption, 'ssl' also accepted
	$phpmail->From = $default_from_email;
	$phpmail->FromName = 'Langenscheidt ISBN Scanner';

	
	$recipients = explode(",", $to);
	foreach($recipients as $k => $recipient) {
		$phpmail->AddAddress($recipient);  // Add a recipient
	}

	$phpmail->WordWrap = 50;                                 // Set word wrap to 50 characters

	$phpmail->IsHTML(false);                                  // Set email format to HTML
	
	$phpmail->Subject = $subject;
	$phpmail->Body    = $message;
		
	$res = $phpmail->Send();
	if(!$res) {

		//echo usermessage('error', $phpmail->ErrorInfo);
		errorlog('Mailer Error: ' . $phpmail->ErrorInfo);

	} else {
		//echo'<pre>';
		//print_r($mail);
		//echo'</pre>';
		$success = 'Mailversand erfolgt (Betreff: '.$subject.')';
		//echo usermessage('success', $success);
		actionlog($success);

	}
		
	return ($res);
				
}


function readFromCSV($file, $firstline_as_header = true, $addfirstline = false) {

	$my_array = array();

	$my_headers = array();

	$row = 0;
	if (($handle = fopen($file, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, false, ";", "'")) !== FALSE) {
			$num = count($data);

			if($row == 0 && $firstline_as_header === true) {
				for ($c=0; $c < $num; $c++) {
					$my_headers[$c] = stripslashes($data[$c]);
				}
				
				if($addfirstline && $firstline_as_header === true) {
				
						foreach($my_headers as $x => $my_header) {
							$my_array[$row][$my_header] = $my_header;												
						}
				} 
			} else {

				$my_array[$row] = array();

				for ($c=0; $c < $num; $c++) {

					if($firstline_as_header === true) {
						//$my_array[$row][$my_headers[$c]] = htmlspecialchars(stripslashes($data[$c]));	
						$my_array[$row][$my_headers[$c]] = stripslashes($data[$c]);	
					} else {
						$my_array[$row][$c] = htmlspecialchars(stripslashes($data[$c]));	
					}

				}
			}


			$row++;
		}
		fclose($handle);
	}

	//flo($my_array);
	return $my_array;

}

function saveToCSV($content_array, $file, $backup = false, $firstline_is_header = false) {

	$content = '';
	
	$my_header = array();

	foreach($content_array as $row => $values) {

		$cells = array();
		$emptycells = 0;

		foreach($values as $col => $cell) {

			if(!is_numeric($col) && $row == 1 && $firstline_is_header === false) {

				$my_header[$col] = "'".addslashes($col)."'";
			}

			if(strlen($cell) == 0) {
				$emptycells++;
			}
			$cells[$col] = "'".addslashes($cell)."'";
		}

		$line = (implode(";", $cells))."\n";

		if($emptycells < count($cells)) {
			$content .= $line;
		} else {
			// Empty lines will not be added!
		}
	}

	if(count($my_header) > 0) {
		$content = implode(";", $my_header)."\n".$content;
	}
	
	$html = '';

	
	if(file_put_contents($file, $content)) {
		$html .= usermessage('success', 'Datei "'.$file.'" wurde erfolgreich gespeichert!');
	} else {
		$html .= usermessage('error', 'Datei "'.$file.'" konnte nicht gespeichert werden!');
	}




	if($backup) {

		$backupfile = '';
		$fileparts = explode('/', $file);
		foreach($fileparts as $i => $filepart) {
	
			if($i == 0) {
				//$backupfile .= $filepart;
			} else if ($i == count($fileparts)-1) {
				$backupfile .= '../data_backup/'.$filepart.'.backup_'.time();
			} else {
				$backupfile .= '/'.$filepart;
			}
		}

		if(file_put_contents($backupfile, $content)) {
			$html .= usermessage('success', 'Backup-Datei "'.$backupfile.'" wurde erfolgreich gespeichert!');
		} else {
			$html .= usermessage('error', 'Datei "'.$backupfile.'" konnte nicht gespeichert werden!');
		}
	}

	return $html;
}


function csvLineToArray($line) {
	
		// Je nach Dateiformat (CSV mit Text-Delimiter oder ohne ) wird an "," oder nur an ; umgebrochen
		if(strpos($line, '";"') === false) {
			$columns = explode(';', trim(trim($line),'"'));
		} else {
			$columns = explode('";"', trim(trim($line),'"'));
		}
		
		return $columns;
		
}


function usermessage($type = 'info', $string) {
	
	if($type == 'success') {

		$content = '<div class="alert alert-success"><a class="close" data-dismiss="alert">x</a><strong>'.$string.'</strong></div>'."\n";

	} else if($type == 'error') {

		$content = '<div class="alert alert-error"><a class="close" data-dismiss="alert">x</a><strong>'.$string.'</strong></div>'."\n";

	} else {

		$content = '<div class="alert alert-info"><a class="close" data-dismiss="alert">x</a><strong>'.$string.'</strong></div>'."\n";

	}
	return $content;
}

function errorlog($string) {
	
	global $LOGFILES; 

	$file = $LOGFILES['errors']; 

	// Open the file to get existing content
	$current = file_get_contents($file);
	// Append a new person to the file
	$current .= date('Y-m-d H:i:s').' - '.$string."\n";
	// Write the contents back to the file
	file_put_contents($file, $current);
	
	actionlog($string);

	//echo usermessage('error', $string);
}

function useractionlog($string) {
	
	global $LOGFILES; 

	$file = $LOGFILES['useractions']; 

	// Open the file to get existing content
	$current = file_get_contents($file);
	// Append a new person to the file
	$current .= date('Y-m-d H:i:s').' - '.$string."\n";
	// Write the contents back to the file
	file_put_contents($file, $current);
	
	actionlog($string);
}




function actionlog($string) {

	global $LOGFILES; 
	
	$file = $LOGFILES['actions']; 

	// Open the file to get existing content
	$current = file_get_contents($file);
	// Append a new person to the file
	$current .= date('Y-m-d H:i:s').' - '.$string."\n";
	// Write the contents back to the file
	file_put_contents($file, $current);



}


function flo($ar) {
	echo'<pre>';
	print_r($ar);
	echo'</pre>';
}


function passwordhash($passwort) {

	$salt = '_=nE9V6bfq*2h6k#Q4.pa|QZcm?Oa3nqB+t|XL>[Cr>+@d~&<bVK}j`s{w3w}kL-';
	
	$passworthash = crypt($passwort, '$6$rounds=5000$'.$salt.'$');

	return $passworthash;

}

function browser_info($agent=null) {
  // Declare known browsers to look for
  $known = array('msie', 'firefox', 'safari', 'webkit', 'opera', 'netscape',
    'konqueror', 'gecko');

  // Clean up agent and build regex that matches phrases for known browsers
  // (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
  // version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"
  $agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
  $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';

  // Find all phrases (or return empty array if none found)
  if (!preg_match_all($pattern, $agent, $matches)) return array();

  // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
  // Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
  // in the UA).  That's usually the most correct.
  $i = count($matches['browser'])-1;
  return array($matches['browser'][$i] => $matches['version'][$i]);
}

?>