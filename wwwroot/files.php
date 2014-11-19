<?php
require_once('_functions/functions.php');

checkrights('user', $USERDATA);

$dirs = array();
$dirs[0]  = $DIR_010_TELEKOM_INCOMING;
$dirs[1]  = $DIR_020_TELEKOM_PROCESSING;
$dirs[2]  = $DIR_030_ZIP_PROCESSING;
$dirs[3]  = $DIR_040_TELEKOM_IMPORT_ARCHIVE;
$dirs[4]  = $DIR_050_IMPORT_DIR;
$dirs[5]  = $DIR_060_IMPORTED_FILES;
$dirs[6]  = $DIR_070_SENT_FILES;
$dirs[7]  = $DIR_990_TRASH;
$dirs[8]  = $DIR_900_FAILED_IMPORTS;
$dirs[9]  = '../source_backup/';
$dirs[10] = '../wwwroot/';
$dirs[11] = '.data/';
$dirs[12] = '.logs/';


include('inc/header.php');

$html .= '<h1>Neu angelieferte Dateien</h1>';
$html .= loadPendingFiles($dirs[0]);

$html .= '<h1>Fehler beim Entpacken (Unzip)</h1>';
$html .= loadPendingFiles($dirs[1]);

$html .= '<h1>Bereit zur Prozessierung</h1>';
$html .= loadPendingFiles($dirs[4]);
$html .= '<p style="width: 500px;">Nach Prozessierung der angelieferten Dateien bleiben bestimmte Dateien in diesem Verzeichnis (z.B. SAP-Buchungsmappen: mu.pda.bufa_[...], mu.pks.bufa_[...], mu.pkr.bufa_[...], mu.pdr.bufa_[...], mu.pka.bufa_[...] und Rechnungsdokumente von 0-Euro-Fakturen. Diese Dateien können problemlos gelöscht werden, nachdem der Versand einer Abrechnung erfolgreich durchgeführt wurde.';
$html .= '<br><br><a href="cleandir.php" class="button red"><span>&#10003;</span>Ja, Verzeichnis leeren</a></p>';

$html .= '<h1>Übersicht Datei-Bestand</h1>';
$html .= '<table id="folders" width="500">';

foreach($dirs as $dir) {
	
	$html .= '<tr><td>'.$dir.'</td><td>'.size_readable(foldersize($dir)).'<td></tr>';
	
}
$html .= '</table>';

print($html);
include('inc/footer.php');
die();


function loadPendingFiles($path) {
	$html .= '<table id="folders" width="500">';
	$html .= '<tr><td>Filename</td><td>Size</td><td>Filetime</td><td>Owner</td></tr>';

	
	if ($handle = opendir($path)) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != "..") {
            $html .= '<tr><td>'.$file.'</td><td>'.size_readable(filesize($path.'/'.$file)).'</td><td>'.date('d.m.Y H:m:s',filemtime($path.'/'.$file)).'</td><td>'.fileowner($path.'/'.$file).'</td></tr>';
        }
    }
    closedir($handle);
	}
	$html .= '</table>';
	return $html;
}




?>