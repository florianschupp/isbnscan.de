<?php
require_once('_functions/functions.php');

//error_reporting(E_ALL ^ E_NOTICE);
//ini_set('display_errors', TRUE);  
//ini_set('error_reporting', E_ALL);

include('inc/header.php');

checkrights('admin', $USERDATA);

makeSystemBackup();

function old_makeSystemBackup() {

$dirs = array();
$dirs[] = '../wwwroot/';

$files = array();

$html = '';	
$html .= '<h1>Backup erzeugen</h1>';

$zippedfiles = array();

$html .= '<table id="folders">';

$filename= '../source_backup/backup_'.time().'.zip';

$res = recursiveZip($dirs[0], $filename);

if(file_exists($filename)) {


	$za = new ZipArchive(); 

	$za->open($filename); 
	echo'<pre>';
	for( $i = 0; $i < $za->numFiles; $i++ ){ 
	    $stat = $za->statIndex( $i ); 
	    //print_r( basename( $stat['name'] ) . PHP_EOL ); 
	}


	echo usermessage('success', 'Datei '.$filename.' (Dateigr&ouml;&szlig;e: '.size_readable(filesize($filename)).', Anzahl Dateien: '.$za->numFiles.') wurde angelegt');

	if(!$res){
		echo usermessage('error', 'Zip nicht erfolgreich:');
	} else {
		echo usermessage('success', 'Zip erfolgreich: '.$filename);
	}

} else {
	echo usermessage('error', 'Datei '.$filename.' wurde nicht angelegt');

}


}







die();

$listfiles = array();


function old_recursiveZip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}


function old_foldersize($path) {

    $total_size = 0;
    $files = scandir($path);


    foreach($files as $t) {

        if (is_dir(rtrim($path, '/') . '/' . $t)) {

            if ($t<>"." && $t<>"..") {

                $size = foldersize(rtrim($path, '/') . '/' . $t);

                $total_size += $size;
            }
        } else {

            $size = filesize(rtrim($path, '/') . '/' . $t);

            $total_size += $size;
        }   
    }

    return $total_size;
}



?>