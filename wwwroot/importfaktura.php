<?php

require_once('_functions/functions.php');

include('inc/header.php');

checkrights('user', $USERDATA);

$job = false;
$confirm = false; 

if(isset($_GET["job"])) {
	$job = $_GET["job"];
} else {
	//die('Job not defined!');
}


if(isset($_GET["confirm"])) {
	$confirm= $_GET["confirm"];
} else {
	//die('Job not defined!');
}



if(!$ALLOW_IMPORT_ONLY) {

	echo usermessage('error', 'Funktion nicht aktiviert');
	die();	
}

if($job) {

	$olddirname = $DIR_060_IMPORTED_FILES.$job.'/';


	$newdirname = $DIR_070_SENT_FILES.$job.'/';


	if($confirm) {

					
		if (!dirmv($olddirname, $newdirname, true, '')) {

			echo usermessage('error','Verzeichnis '.$olddirname.' konnte nicht umbenannt werden!');
			errorlog('Verzeichnis '.$olddirname.' konnte nicht umbenannt werden!');
			return;
		} else {
			echo usermessage('success','Faktur wurde importiert nach '.$newdirname.'!');

			$html = '';
			$html .= '<p><label class="textinput"><label>';
			$html .= '<div class="container"><a href="send.php?job=next" class="button green"><span>&times;</span>Weiter</a></div>';
			$html .= '</p>';
			print($html);
		}
	} else {


		echo dialog('modal', 'Faktura ohne Mailversand importieren?', 'Die Dateien werden ins Verzeichnis "'.$newdirname.'" verschoben.', 'importfaktura.php?job='.$job.'&confirm=true', 'send.php');

	}
}

include('inc/footer.php');