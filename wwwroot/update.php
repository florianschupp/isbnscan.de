<?php

require_once('_functions/functions.php');

include('inc/header.php');

error_reporting(E_ALL ^ E_NOTICE);
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', TRUE);  

if(gethostname() != $HOSTNAME_PRODUKTION) {

	$message = 'Die Update Funktion ist nur auf dem Produktiv-System verf&uuml;gbar!';

	echo usermessage('error', $message);
	die();
} else {
	$message = usermessage('success', 'Sie befinden sich auf dem produktiv-System. Update Funktion verfügbar');

	// Prüfen ob ein Update vorliegt
	$update = checkSystemUpdate();

	if($update === false){
	
		$message = 'Auf dem Staging-System wurde kein Update gefunden'; 
			
		echo usermessage('error', $message);
	
		die();
		
	}
}


checkrights('admin', $USERDATA);

$confirm = false;
$update = false;

if(isset($_GET["confirm"])) {
	$confirm= $_GET["confirm"];
} else {
	//die('Job not defined!');
}


//$origin = 'www-data@pubcms8302:/srv/www/wwwroot/';

$origin = 'pubcms8302:/srv/www/wwwroot/';
$destination = '/srv/www/wwwroot/';

if($confirm) {

	echo '<h1>System-Update wird durchgeführt</h1>';

	$sync_parameter = '-avpzu --delete --exclude-from=/srv/www/rsync_exclude.txt --chmod=a+rw,g+rw,o-w -e "ssh"';

	makeSystemBackup();

	$command = 'rsync ' . $sync_parameter . ' ' . $origin. ' ' . $destination. '';

	echo usermessage('info', 'Rsync Kommando: '.$command.'');

	exec($command, $response);

	$filelist = getFileListFromOutput($response);

	if(count($response) == 0) {

		echo usermessage('error', 'Rsync-Befehl hat keinen Output erzeugt. Vermutlich liegt ein Fehler vor!');

	} else if(count($response) == 1 AND $response[0] == 'receiving incremental file list') {

		echo usermessage('info', 'Es wurden keine Dateien kopiert. Das System ist auf aktuellem Stand.');

	} else {


		echo usermessage('success', '<pre>'.print_r($response, true).'</pre>');


		echo usermessage('success', 'Rsync abgeschlossen');
	}


} else {


	// Parameter -n für Dry-Run
	$sync_parameter = '-avpzu -n --delete --exclude-from=/srv/www/rsync_exclude.txt --chmod=a+rw,g+rw,o-w -e "ssh"';

	$command = 'rsync ' . $sync_parameter . ' ' . $origin. ' ' . $destination. '';

	$message .= usermessage('info', 'Rsync Kommando: '.$command.'');

	exec($command, $response);
	$filelist = getFileListFromOutput($response);

	$message .= usermessage('info', '<b>Ergebnis des Rsync Dry-Run</b><pre>'.print_r($filelist, true).'</pre>');

//	flo($filelist);

	if(count($response) <= 4) {

		echo '<h1>System-Update Dry-Run</h1>';

		echo $message;

		echo usermessage('error', 'Es liegt kein Update vor.');

	} else {

		$message .= usermessage('success', 'Es liegt ein Update vor.');

		$message .= usermessage('error', '<em>Warnung!</em> Alle oben genannten Dateien werden mit dem Update unwiderruflich überschrieben!');

		echo dialog('modal', 'System-Update durchführen?', $message, 'update.php?confirm=true', 'home.php');

	}


}


function getFileListFromOutput($output) {

	$cue = false;
	$filelist = array();

	foreach($output as $k => $string) {

		if($string == 'receiving incremental file list') {

			$cue = true;
			continue;

		} else if (substr($string, 0,9) == 'sent ' OR $string == '') {

			$cue = false;

		}

		if($cue === true) {

	
			if(substr($string, 0,9) == 'deleting ') {

				$filelist['delete'][] = substr($string, 9);

			} else if(substr($string, 0,2) == './') {

			} else {

				$filelist['change'][] = $string;

			}



		}
	}

	return $filelist;
}


include('inc/footer.php');
die();




function checkSystemUpdate() {

	return true;
}


?>