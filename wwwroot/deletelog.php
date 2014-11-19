<?php

require_once('_functions/functions.php');

require_once('classes/class.phpmailer.php');

include('inc/header.php');

checkrights('admin', $USERDATA);

$log = false;
$confirm = false; 

if(isset($_GET["log"])) {
	$log = $_GET["log"];
} else {
	//die('Job not defined!');
}


if(isset($_GET["confirm"])) {
	$confirm= $_GET["confirm"];
} else {
	//die('Job not defined!');
}


if($log) {

	if($log == 'errors') {
	
		$filename = $LOGFILES['errors']; 
	
	} else if($log == 'actions') {

		$filename = $LOGFILES['actions']; 
	} else if($log == 'useractions') {

		$filename = $LOGFILES['useractions']; 
	} else {
	
		echo usermessage('error', 'Zugriff verweigert!');
		die();
		
	}

	if($confirm) {


		if(file_exists($filename)) {
			if(unlink($filename)) {
				file_put_contents($filename, '');
				
				//actionlog('Logdatei '.$filename.' wurde neu angelegt');

				echo usermessage('success', 'Logdatei '.$filename.' wurde neu angelegt');

			} else {
				errorlog('Logdatei '.$filename.' konnte nicht gelöscht werden!');
			}
		} else {
			file_put_contents($filename, '');
			actionlog('Logdatei '.$filename.' wurde neu angelegt');
		}

	} else {

		echo dialog('modal', 'Logdatei löschen?', 'Soll die Logdatei "'.$filename.'" wirklich gelöscht werden?<br/>', 'deletelog.php?log='.$log.'&confirm=true', 'home.php');

	}
}

include('inc/footer.php');