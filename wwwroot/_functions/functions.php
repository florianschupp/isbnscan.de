<?php
ini_set('display_errors', FALSE);  

require_once('base_functions.php');
require_once('_config/config.php');

session_start();

// Theme setzen
if(isset($_GET["theme"])) {
	$theme = $_GET["theme"];

} else {
	if(array_key_exists('theme', $_SESSION)) {
		$theme = $_SESSION['theme'];
	} else {
		$theme = 'dark';
	}
	$_SESSION['theme'] = $theme;
}

$managers = load_accountmanager($ACCOUNTMANAGER_FILE);

$accounts = load_partners($PARTNERS_FILE);

// Prüfe, ob ein Login besteht und noch gültih ist und leite ggf. zur Login-Maske weiter
if(!isset($_SESSION['username'])) {

	header('Location: login.php');
	exit;
} else {

	$session_idletime = time()-$_SESSION['last_access'];
	$session_age = time()-$_SESSION['starttime'];
	
	if($session_idletime > $SESSION_IDLE_LIFETIME) {
		header('Location: login.php?message=idletime');
		die();
	} else if($session_age > $SESSION_LIFETIME) {
		header('Location: login.php?message=session_lifetime');
		die();		
	} else {
		$USERDATA = loaduser($USERS_FILE);
	}
}


// Prüfe vor jeder Aktion, ob der Username noch existiert und noch aktiv ist 
validateuser();

// Alle Accounts auf vollständige Konfiguration prüfen
validateAccounts($accounts);





function getSalesmodel($faktura_id) {
		
	if(($faktura_id >= 1000000) && ($faktura_id < 2000000 )) {
		$salesmodel = 'Proforma';
	} else if(($faktura_id >= 2000000) && ($faktura_id < 3000000 )) {
		$salesmodel = 'Reseller';			
	} else if(($faktura_id >= 3000000) && ($faktura_id < 4000000 )) {
		$salesmodel = 'Agency';
	} else if(($faktura_id >= 4000000) && ($faktura_id < 5000000 )) {
		$salesmodel = 'Reseller';
	} else if(($faktura_id >= 5000000) && ($faktura_id < 6000000 )) {
		$salesmodel = 'Service';
	} else if(($faktura_id >= 8000000) && ($faktura_id < 9000000 )) {
		$salesmodel = 'Agency';
	}
			
	return $salesmodel;
}

function checkrights($needed = 'user', $USERDATA = false) {

	if(!$USERDATA) {
		echo usermessage('error', '$USERDATA nicht gesetzt!');
		die();
	}	
	
	if(!hasrights($needed, $USERDATA)) {
		echo usermessage('error', 'Keine Berechtigung!');
		die();
	}
}


function hasrights($needed = 'user', $USERDATA = false) {
	
	if(!in_array($needed, $USERDATA['roles'])) {
		return false;
		die();
	} else {

		return true;	
	}
}

function validateuser() {
	global $USERS_FILE;


	$client_ip = '';
	
	if ( isset($_SERVER["REMOTE_ADDR"]) )    {
	    $client_ip = $_SERVER["REMOTE_ADDR"];
	} else if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) )    {
	    $client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else if ( isset($_SERVER["HTTP_CLIENT_IP"]) )    {
	    $client_ip = $_SERVER["HTTP_CLIENT_IP"];
	} 
	
	$USERDATA = loaduser($USERS_FILE);
	
	if($USERDATA['status'] != 'active') {
	

		if ($USERDATA['status'] == 'initialpassword') {
	
			include('change_password.php');
	
			die();
			
		} else {
				
			// Access forbidden:
			header('HTTP/1.1 403 Forbidden');
	
			useractionlog('Zugriff verweigert "'.$USERDATA['username'].'" IP-Adresse: '.$client_ip.' Host: '.gethostbyaddr($client_ip));
	
			echo 'Zugriff verweigert!';	
	
			die();
					
		}
		

		
	} 
}

function loaduser($file) {

	if(!isset($_SESSION['username'])) {
		return false;
	}

	$filecontent = readFromCSV($file, true);

	foreach($filecontent as $i => $user) {

		if($user['username'] == $_SESSION['username']) {
			
			$user['roles'] = explode(',', $user['roles']);
			
			foreach($user['roles'] as $i => $role) {
				$user['roles'][$i] = trim($role);
			}

			return $user;
		}
	}
}

function load_partners($file) {

	$accounts = array();

	$array = readFromCSV($file);

	foreach($array as $i => $account) {

		$accounts[$account['id']] = $account;
	}

	return $accounts;
}


function load_accountmanager($file) {

	$filecontent = readFromCSV($file, true);

	foreach($filecontent as $i => $account) {
	
		$accounts[$account['id']] = $account;

	}

	return $accounts;
}





function validateAccounts($accounts) {
	
	$errors = array();

	foreach ($accounts as $k => $v) {
	
		if(validatePartner($v)) {
		
			$errors[$k] = validatePartner($v);
		}
	
	}

	if(count($errors) > 0) {
	
		print_r($errors);
		//die();
	
	}
		
}


function validatePartner($partner) {

	if (!array_key_exists('partnertype', $partner)) return "error_partnertype";
	if (!array_key_exists('billing', $partner)) return "error_billing";
	if ($partner['billing'] != "Gutschrift" AND $partner['billing'] != "Rechnung") return "error_billing";
	if (!array_key_exists('name', $partner)) return "error_name";
	if (!array_key_exists('to_emails', $partner)) return "error_to_emails";

	return false;

}



function size_readable($size, $max = null, $system = 'si', $retstring = '%01.2f %s')
{
    // Pick units
    $systems['si']['prefix'] = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $systems['si']['size']   = 1000;
    $systems['bi']['prefix'] = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
    $systems['bi']['size']   = 1024;
    $sys = isset($systems[$system]) ? $systems[$system] : $systems['si'];
  
    // Max unit to display
    $depth = count($sys['prefix']) - 1;
    if ($max && false !== $d = array_search($max, $sys['prefix'])) {
        $depth = $d;
    }
  
    // Loop
    $i = 0;
    while ($size >= $sys['size'] && $i < $depth) {
        $size /= $sys['size'];
        $i++;
    }
  
    return sprintf($retstring, $size, $sys['prefix'][$i]);
}

function listBookmarks() {
	
	global $USERDATA;

	$bookmarkfile = '.data/bookmarks/'.$USERDATA['username'].'.bookmarks';
	
	if(file_exists($bookmarkfile)) {
		$bookmarks = unserialize(file_get_contents($bookmarkfile));	
	} else {
		
		$bookmarks = array();
	}
	
	$html = '';
	
	if(count($bookmarks) == 0) {
		$html .= '<li><a href="bookmarks.php?action=add&url='.rawurlencode(curPageURL()).'"><span>Bookmark speichern</span></a></li>';
	} else {
		foreach($bookmarks as $i => $bookmark) {
	
			$html .= '<li><a href="'.rawurldecode($bookmark['url']).'"><span>'.rawurldecode($bookmark['label']).'</span></a></li>';
	
		}		
	}

	echo $html;	
}

function listSentJobs($type, $status = 'sent') {

	return listMailJobs($type, $status);

	
}

function listPeriods($limit_year = false) {

	global $accounts;

	$periods = loadDir(false, false, 'sent');
		
	$file= 'period_overview.php';
		
	$hierachy = array();
	foreach($periods  as $k => $string) {
		
		$parts = explode('_', $string);
		$hierachy[substr($parts[0], 0,4)][substr($parts[0], 5,2)] = substr($parts[0], 0,7);

	}

	$html = '';
	foreach($hierachy as $year => $periods) {
	
			arsort($periods);
			
			if(!$limit_year) {
				$html .= '<li class=\'has-sub\'><a href="mailcalender.php?year='.$year.'">Kalenderjahr '.$year.'</a>';
				$html .= '<ul>';				
			}

		foreach($periods as $i => $period){
				$parts = explode('-', $period);
				$html .= '<li><a href="period_overview_grouped.php?period='.htmlentities($period).'"><span>Abrechnungsperiode '.$parts[1].'/'.$parts[0].'</span></a></li>';
			}
			
			if(!$limit_year) {
				$html .= '</ul>';
				$html .= '</li>';
			}
	}

	return $html;
}

function listMailJobs($type = false, $status = false) {

	global $accounts;

	$periods = loadDir(false, false, $status);
		
	if($status == 'sent') {
		$file= 'view.php';
	} else {
		$file= 'send.php';
	}
		
	$hierachy = array();
	$group_hierachy = array();

	foreach($periods  as $k => $string) {
		
		$parts = explode('_', $string);

		$group_hierachy[substr($accounts[$parts[1]]['partnertype'],0,1).' - '.$accounts[$parts[1]]['group']][$accounts[$parts[1]]['id']][] = $string;


	}

	ksort($group_hierachy);


	$html = '';

	foreach($group_hierachy as $group => $hierachy) {


		$html .= '<li class=\'has-sub\'><a href="#">'.$group.'</a>';
		$html .= '<ul>';		

		foreach($hierachy as $distributor => $jobs) {

			$html .= '<li class=\'has-sub\'><a href="'.$file.'?partner='.$distributor.'">'.$accounts[$distributor]['name'].' ('.count($jobs).')</a>';
			$html .= '<ul>';

			foreach($jobs as $i => $job){
				$html .= '<li><a href="'.$file.'?job='.htmlentities($job).'"><span>'.$job.'</span></a></li>';
			}
			$html .= '</ul>';
			$html .= '</li>';

		}
		$html .= '</ul>';
		$html .= '</li>';

	}


	return $html;

}

function countMailJobs() {

	$periods = loadDir();
	return count($periods);

}

function countImports() {
	global $DIR_050_IMPORT_DIR;
	return count(loadImportFiles($DIR_050_IMPORT_DIR));

}

function countZIPImports() {
	global $DIR_010_TELEKOM_INCOMING;
	return count(loadImportFiles($DIR_010_TELEKOM_INCOMING));
}

function listFakturas($period_from = false, $period_to = false, $group = false, $partner_ids = false, $faktura_id = false, $salesmodel = false, $tier = false, $partnertype = false) {
	if($partner_ids == 'all') {
		$partner_ids = false;
	}
	
	global $DIR_070_SENT_FILES, $PARTNERS_FILE;

	if(!is_array($partner_ids) && $partner_ids) {
		$myar = array();
		$myar[] = $partner_ids;
		$partner_ids = $myar;
		unset($myar);
	}


   	if($group && !$partner_ids OR $tier !== false OR $partnertype !== false) {
   	
		$my_partners = load_partners($PARTNERS_FILE);
		
		foreach($my_partners as $pid => $partner) {

			if($partnertype !== false AND $partner['partnertype'] != $partnertype) {
				unset($my_partners[$pid]);
				continue;				
			}
			
			if($tier !== false AND $partner['tier'] != $tier) {
				unset($my_partners[$pid]);
				continue;				
			} else {
			}

			if($group && $group != 'all' && $partner['group'] != $group) {
				unset($my_partners[$pid]);
				continue;
			} 
			$partner_ids[] = $pid;

		}
			
   	 }
   	 
	$my_dirs = array();
	
	$dir = $DIR_070_SENT_FILES;
		
	if ($handle = opendir($dir)) {

   	 	/* Das ist der korrekte Weg, ein Verzeichnis zu durchlaufen. */
   	 	while (false !== ($file = readdir($handle))) {
   	 	
   	 		if ($file != "." && $file != "..") {
   	 		
   	 			$parts = explode('_', $file);
   	 			
   	 			
   	 			if($faktura_id && $parts[2] != $faktura_id) {
	   	 			continue;
   	 			}
   	 			
   	 			if($partner_ids && !in_array($parts[1], $partner_ids)) {
	   	 			continue;
   	 			}

   	 			if($period_from && $parts[0] < $period_from) {
	   	 			continue;
   	 			}

   	 			if($period_to && $parts[0] > $period_to) {
	   	 			continue;
   	 			}
   	 			   	 			
     			$my_dirs[] = $file;
	 	 		unset($parts); 	 			

        	}
   	 	}
	} else {
		echo usermessage('error', 'Verzeichnis '.$dir.' konnte nicht geöffnet werden!');
	}

	return $my_dirs;
	
}

function loadDir($job =false, $partnerid = false, $path = false, $period =false) {

	global $DIR_060_IMPORTED_FILES, $DIR_070_SENT_FILES;

	$ar = array();
	$mar = array();

	if($path == 'sent') {
	
		$dir = $DIR_070_SENT_FILES;
		
	} else {
		
		$dir = $DIR_060_IMPORTED_FILES;

	}

	if($job) {
		$dir = $dir.$job.'/';
	}
		
	if ($handle = opendir($dir)) {

   	 	/* Das ist der korrekte Weg, ein Verzeichnis zu durchlaufen. */
   	 	while (false !== ($file = readdir($handle))) {
   	 	
   	 		if ($file != "." && $file != "..") {
   	 		
   	 			$parts = explode('_', $file);
   	 			
   	 			if($period) {
	   	 			if($parts[0] == $period) {
		     			$mar[] = $parts;
			 	 		unset($parts); 	 			
	   	 			}	 			
   	 			} else {
	   	 			if($partnerid) {
		   	 			if($parts[1] == $partnerid) {
			     			$mar[] = $parts;
				 	 		unset($parts); 	 			
		   	 			}
	   	 			} else {
	   	 				$mar[] = $parts;
	   	 				unset($parts);
	   	 			}
	   	 		   	 
	        		$ar[] = $file;
   	 			}
   	 			

        	}
   	 	}
	} else {
		echo usermessage('error', 'Verzeichnis '.$dir.' konnte nicht geöffnet werden!');
		die();
	}

   	 if(!$job) {
   		unset($ar);
		$ar = array();
	   	 asort($mar);   	
	   	 
	   	 foreach($mar as $foo => $bar) {
		   	 if($bar) { 
			   	 $ar[] = implode('_', $bar);
			 }
		}
   	 }


    closedir($handle);
    
    return $ar;  
}

function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
 }

function dirmv($source, $dest, $overwrite = false, $funcloc = NULL){

  if(is_null($funcloc)){
    $dest .= '/' . strrev(substr(strrev($source), 0, strpos(strrev($source), '/')));
    $funcloc = '/';
  }


	if(!is_dir($dest . $funcloc)) {		
		if (!mkdir($dest . $funcloc, 0777, true)) {
	  	  	errorlog('Erstellung des Verzeichnisses '.$dirname.' schlug fehl!');
	   	 	return;
		}
 	}
         				
         				

  if($handle = opendir($source . $funcloc)){ // if the folder exploration is sucsessful, continue
    while(false !== ($file = readdir($handle))){ // as long as storing the next file to $file is successful, continue
      if($file != '.' && $file != '..'){
        $path  = $source . $funcloc . $file;
        $path2 = $dest . $funcloc . $file;


        if(is_file($path)){
          if(!is_file($path2)){
            if(!@rename($path, $path2)){
              echo '<font color="red">File ('.$path.') could not be renamed, likely a permissions problem.</font>';
            }
          } elseif($overwrite){
            if(!@unlink($path2)){
              echo 'Unable to overwrite file ("'.$path2.'"), likely to be a permissions problem.';
            } else
              if(!@rename($path, $path2)){
                echo '<font color="red">File ('.$path.') could not be moved while overwritting, likely a permissions problem.</font>';
              }
          }
        } elseif(is_dir($path)){
          dirmv($source, $dest, $overwrite, $funcloc . $file . '/'); //recurse!
          rmdir($path);
        }
      }
    }
    
    rmdir($source);
    closedir($handle);
    
    return true;
  }
}

function loadImportFiles($dir = '../050_PALI_IMPORT/') {

	$ar = array();
	
	if ($handle = opendir($dir)) {

   	 	/* Das ist der korrekte Weg, ein Verzeichnis zu durchlaufen. */
   	 	while (false !== ($file = readdir($handle))) {
   	 	
   	 		if ($file != "." && $file != "..") {
        		$ar[] = $file;
        	}
   	 	}
   	 }

    closedir($handle);
    
    return $ar;
   
}



/* creates a compressed zip file */
function create_zip($files = array(),$destination = '',$overwrite = false) {

	//if the zip file already exists and overwrite is false, return false
	if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$valid_files = array();
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			} else {
				errorlog('Datei '.$file.' existiert nicht');
			}
		}
	}
	//if we have good files...
	if(count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			errorlog('Zip Datei konnte nciht angelegt werden');
			return false;
		}
		//add the files
		foreach($valid_files as $file) {
			
			$parts = explode('/', $file);

//			print_r($parts);
//			$parts[3] = substr($parts[3], 8, 10).'.csv';
//			print_r($parts);

			$newfilename = $parts[count($parts)-1];

			$zip->addFile($file,$newfilename);
		}
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
		
		//close the zip -- done!
		$zip->close();
		
		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}


function dialog($type, $title, $text, $confirm_url, $cancel_url) {

			$html = '<h1>'.$title.'</h1>';
			$html .= '<div id="form_container"><form id="form" action="'.$confirm_url.'" method="POST" enctype="multipart/form-data">';
			$html .= '<p>'.$text;
			$html .= '<p><div class="container"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>&#10003;</span>Ja, weiter</a></div><div class="container"><a href="'.$cancel_url.'" class="button red"><span>x</span>Abbrechen</a></div></p>';
			return $html;

}


class rbc_BrowserInfo {

        public $browser = "";
        public $os = "";
        public $lang = "";
        public $mobile = 0;

        public function getBrowserInfo() {

            if (preg_match('/Firefox/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'firefox';
            if (preg_match('/Mozilla/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'firefox';
            if (preg_match('/Opera/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'opera';
            if (preg_match('/Safari/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'safari';
            if (preg_match('/Chrome/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'chrome';
            if (preg_match('/Camino/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'camino';
            if (preg_match('/Konqueror/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'konqueror';
            if (preg_match('/MSIE/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'ie';
            if (preg_match('/Internet Explorer/i',$_SERVER['HTTP_USER_AGENT'])) $this->browser = 'ie';

            if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i', strtolower($_SERVER['HTTP_USER_AGENT'])) || (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml')>0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
                $this->mobile = 1;
            }


            $os_arr = array ( 'windows' => '(Windows)|(Win)', 'linux'=>'(linux)|(X11)', 'mac'=>'(Mac_PowerPC)|(Macintosh)|(Mac)');
            $this->os = 'other';
            foreach($os_arr as $os=>$ospattern) {
                if (eregi($ospattern, $_SERVER['HTTP_USER_AGENT']))
                $this->os = $os; 
            }

            $this->lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }
    }



function viewFaktura($dir, $mode = 'view', $path = false, $mailvalues = false) {

		global $MAIL_FROM_EMAIL, $ALLOW_IMPORT_ONLY;
		global $MAIL_FROM_NAME;
		global $MAIL_TO_BCC;
		global $MAIL_REPLYTO;
		global $DIR_060_IMPORTED_FILES, $DIR_070_SENT_FILES;
		
		global $accounts, $managers, $MESSAGEFILES, $next_job, $prev_job, $BOILERPLATE_FILE;
		
		if($path == 'sent') {
			$filepath = $DIR_070_SENT_FILES;
			$thisfile = 'view.php';
		} else {
			$filepath = $DIR_060_IMPORTED_FILES;
			$thisfile = 'send.php';

		}
				
		if($mode == 'view' OR $mode == 'proforma') {
			$preview = true;
		} else {
			$preview = false;
		}
			
		$html = '';
		
		// Jobs und Navigation erzeugen
		$alljobs = loadDir(false, false, $path);	


		if(count($alljobs) == 0) {
			echo usermessage('error', 'Die angegebene Faktura-ID existiert nicht in diesem Verzeichnis');
			return false;
		}
	
		foreach($alljobs as $k => $v) {
			if($v == $dir) {
				if($k > 0) {
					$prev_job = $alljobs[$k-1];
				} else {
					$prev_job = $alljobs[count($alljobs)-1];
				}
				
				if(array_key_exists($k+1, $alljobs)) {
					$next_job = $alljobs[$k+1];
				} else {
					$next_job = $alljobs[0];
				}
			}
		}
		
		
		$parts = explode("_", $dir);
		
		$period = $parts[0];
		$partner_id = $parts[1];
		$faktura_id = $parts[2];
		
		
		if(!array_key_exists($partner_id,$accounts)) {
				errorlog('Gesch&auml;ftspartner "'.$partner_id.'" nicht definiert');
				return;
		}
		
		$partnerconfig = $accounts[$parts[1]];

		// Zahlungseingang vs. Zahlungsausgang
		if($partnerconfig['partnertype'] == 'Kreditor') {
			$billingfactor = '-1';		
		} else {
			$billingfactor = '1';		
		}
		
		$files = loadDir($dir, false, $path);

		$mail = array();


		// Nur die Files fÃƒÂ¼r den Anlagentext auflisten		
		$mail['path'] = $filepath.$dir.'/';
		$fileinfo = '';


		foreach($files as $foo => $bar) {	

			if($bar != 'CSV' && $bar != 'billingdata.cache' && $bar != 'statistics.cache') {				
				$fileinfo .= $bar.' ('.size_readable(filesize($mail['path'].$bar)).')'."\n";

				$parts = explode(".", $bar);

				if(array_key_exists(2, $parts)) {
					if($parts[2] == 'zip' && $parts[1] == 'csv') {

						$evn_file = $mail['path'].'CSV/'.$parts[0].'.'.$parts[1];

						if(file_exists($evn_file)) {
							$amounts = readEVN($evn_file, $partnerconfig);
						} else {

							$res = unzip($mail['path'].$bar, $mail['path'].'CSV/', true, true);

							if(!$res) {
								flo('Datei '.$evn_file.' fehlt');
							} else {
								$amounts = readEVN($evn_file, $partnerconfig);							
							}

						}



						$evn_filename = $parts[0];

						$billinginfos = explode('_', $evn_filename);
						//flo($billinginfos);
			
						if($billinginfos[0] == 'EVN') {
							$salesperiod_start = substr($billinginfos[4],0,2).'.'.substr($billinginfos[4],3,2).'.'.substr($billinginfos[4],6,4);
							$salesperiod_end = substr($billinginfos[5],0,2).'.'.substr($billinginfos[5],3,2).'.'.substr($billinginfos[5],6,4);
			
							$salesperiod_start_timestamp = strtotime($salesperiod_start.' 00:00:00'); 
							$salesperiod_end_timestamp = strtotime($salesperiod_end.' 23:59:59'); // Ende des Abrechnungstages
			
							$file_timestamp = substr($billinginfos[6],6,2).'.'.substr($billinginfos[6],4,2).'.'.substr($billinginfos[6],0,4).' '.substr($billinginfos[6],8,2).':'.substr($billinginfos[6],10,2).':'.substr($billinginfos[6],12,2);
			
						} else if($billinginfos[0] == 'PROF') {
							$salesperiod_start = '-';
							$salesperiod_end = '-';
							$file_timestamp = '-';
						}

						$amounts['salesperiod_start'] = $salesperiod_start;						
						$amounts['salesperiod_end'] = $salesperiod_end;						
					}
				}
				
			}
			
		}

		if(!array_key_exists($accounts[$partner_id]['accountmanager'],$managers)) {
			errorlog('Accountmanager "'.$accounts[$partner_id]['accountmanager'].'" fÃƒÆ’Ã‚Â¼r Partner-ID '.$partner_id.' nicht definiert');
			return;
			
		} else {
			$accountmanager = $managers[trim($accounts[$partner_id]['accountmanager'])];
		}
		
		
		if($mailvalues) {
		
			// Validate TO E-Mails
			$to_emails = explode(';', str_replace(',', ';', $mailvalues['to']));
			$mailvalues['to'] = '';
			$valid_mails = array();
			foreach($to_emails as $k => $to_email) {
				$to_email = trim($to_email);
				if($to_email) {
					if(validate_email($to_email)) {
						$valid_mails[] = $to_email;
					} else {
						$html .= usermessage('error', 'Die E-Mail Adresse "'.$to_email.'" ist fehlerhaft und wurde entfernt!');
						$preview = true;
					}
				} else {
					
					$html .= usermessage('error', 'Es wurde kein Empf&auml;nger E-Mail Adresse angegeben.');
					$preview = true;
					
				}
			}
			$mailvalues['to'] = implode(',', $valid_mails);
	
	
			// Validate BCC E-Mails
			$bcc_emails = explode(';', str_replace(',', ';', $mailvalues['bcc']));
			
			//print_r(count($bcc_emails));
			$valid_mails = array();
			$mailvalues['bcc'] = '';
			$valid_mails = array();
			foreach($bcc_emails as $k => $bcc_email) {
				$bcc_email = trim($bcc_email);
				if($bcc_email) {
					if(validate_email($bcc_email)) {
						$valid_mails[] = $bcc_email;
					} else {
						$html .= usermessage('error', 'Die BCC E-Mail Adresse "'.$bcc_email.'" ist fehlerhaft und wurde entfernt!');
						$preview = true;
					}
				}
			}
			$mailvalues['bcc'] = implode(',', $valid_mails);

			// Validate From E-Mail
			$mailvalues['from_mail'] = trim($mailvalues['from_mail']);
			if($mailvalues['from_mail']) {
				if(!validate_email($mailvalues['from_mail'])) {
					$html .= usermessage('error', 'Die Absender E-Mail Adresse "'.$mailvalues['from_mail'].'" ist fehlerhaft!');
					$preview = true;
				}				
			} else {
				$html .= usermessage('error', 'Es wurde kein Absender E-Mail Adresse angegeben.');
				$preview = true;
			}

			

				
			// Validate From E-Mail
			$mailvalues['replyto'] = trim($mailvalues['replyto']);
			if($mailvalues['replyto']) {
				if(!validate_email($mailvalues['replyto'])) {
					$html .= usermessage('error', 'Die Reply-To E-Mail Adresse "'.$mailvalues['from_mail'].'" ist fehlerhaft!');
					$preview = true;
				}				
			} else {
				$html .= usermessage('error', 'Es wurde kein Reply-To E-Mail Adresse angegeben.');
				$preview = true;
			}

			$boilerplate_template = file_get_contents($BOILERPLATE_FILE, true);

			$mailvalues['message'] = str_replace('###HTML_MAILCONTENT###', nl2br($mailvalues['message']), $boilerplate_template);
				
			$mail = $mailvalues;

			
		} else {

			$mail['from_mail'] = $accountmanager['email'];
			$mail['from_name'] = $accountmanager['name'];
			$mail['replyto'] = $accountmanager['email'];

//flo($partnerconfig);

			$mail['bcc'] = $accountmanager['email'];	

			// Kein Versand an Buchhaltung, wenn keine Teilnahme am Gutschriftsverfahren
			if($partnerconfig['partnertype'] == 'Kreditor' && $partnerconfig['billing'] == 'Rechnung'){
				//$mail['bcc'] = $MAIL_TO_BCC;
			} else {
				$mail['bcc'] = $mail['bcc'].';'.$MAIL_TO_BCC;	
			}

			$mail['to'] = $partnerconfig['to_emails'];
			
			$periodstring = $amounts['salesperiod_start'].' - '.$amounts['salesperiod_end'];

			if(($faktura_id >= 1000000) && ($faktura_id < 2000000 )) {
				$salesmodel = 'Proforma';
			} else if(($faktura_id >= 2000000) && ($faktura_id < 3000000 )) {
				$salesmodel = 'Resellermodell';			
			} else if(($faktura_id >= 3000000) && ($faktura_id < 4000000 )) {
				$salesmodel = 'Agencymodell';
			} else if(($faktura_id >= 4000000) && ($faktura_id < 5000000 )) {
				$salesmodel = 'Resellermodell';
			} else if(($faktura_id >= 5000000) && ($faktura_id < 6000000 )) {
				$salesmodel = 'Service';
			} else if(($faktura_id >= 8000000) && ($faktura_id < 9000000 )) {
				$salesmodel = 'Agencymodell';
			}

			$templatevalues['###SALESMODEL###'] = $salesmodel;

			if($partnerconfig['partnertype'] == "Debitor") {
				$mail['subject'] = 'Abrechnung '.$periodstring.' - für '.$partnerconfig['name'].' ('.$partner_id.' / Rechnungsnr. '.$faktura_id.' / '.$salesmodel.')';		
			} else {
				$mail['subject'] = 'Verlagsabrechnung '.$periodstring.' - für '.$partnerconfig['name'].' ('.$partner_id.' / '.$faktura_id.' / '.$salesmodel.')';		
			}

			//$contactperson_name = $accounts[$partner_id]['contactperson_first'].' '.$accounts[$partner_id]['contactperson_last'];

			// Setting Variables fpr usage in Predefined E-Mail-Body
			//


			$templatevalues['###ACCOUNTMANAGER_FOOTER###'] = str_replace('<br />', "\n", $accountmanager['footer']);
			$templatevalues['###ANSPRECHPARTNER###'] = $accounts[$partner_id]['anrede'].' '.$accounts[$partner_id]['contactperson_first'].' '.$accounts[$partner_id]['contactperson_last'];
			$templatevalues['###ACCOUNT_NAME###'] = $accounts[$partner_id]['name'];
			$templatevalues['###BILLING_PERIOD###'] = $periodstring;
			$templatevalues['###ATTACHMENTS###'] = $fileinfo;

			if($accounts[$partner_id]['partnertype'] == 'Kreditor') {
				$factor = -1;
			} else {
				$factor = 1;
			}

			$templatevalues['###BILLING_AMOUNT###'] = number_format(($factor)*$amounts['billing_amount']['EUR'], 2, ',', '.');

			$templatevalues['###NET_UNIT_PRICE_SUM###'] = number_format($amounts['net_unit_price']['EUR'], 2, ',', '.');
			$templatevalues['###AVERAGE_REBATE###'] = number_format(100*round($amounts['billing_amount']['EUR'] / $amounts['net_unit_price']['EUR'], 3), 1, ',', '.');			
			$templatevalues['###STORNO_COUNT###'] = number_format($amounts['kpi']['transaction_count']['Storno'], 0, ',', '.');
			$templatevalues['###TRANSACTION_COUNT###'] = number_format($amounts['kpi']['transaction_count']['Einzelkauf'], 0, ',', '.');
			
			// Richtige Mailvorlage wÃƒÂ¤hlen
			if(array_key_exists(strtolower($accounts[$partner_id]['partnertype']), $MESSAGEFILES)) {
				
				$file = $MESSAGEFILES[strtolower($accounts[$partner_id]['partnertype'])];

			} else {

				$file = $MESSAGEFILES['default'];

			}


			$messagecontent = file_get_contents($file, true);
			
			foreach($templatevalues as $key => $value) {
				$messagecontent = str_replace($key, $value, $messagecontent);
			}

			$mail['message'] = $messagecontent;
		}


		// Mail Attachments sammeln
		$mail['path'] = $filepath.$dir.'/';

		$valid_filetypes = array('zip', 'pdf', 'xml');

		foreach($files as $foo => $bar) {	

			$parts = explode('.', $bar);

			if(in_array($parts[count($parts)-1], $valid_filetypes)) {
				$mail['files'][] = $bar;
			}
		}

		if($preview) {

			if($ALLOW_IMPORT_ONLY && $path != 'sent') {

				$html .= '<p><label class="textinput"><label>';
				$html .= '<div class="container"><a href="importfaktura.php?job='.$dir.'" class="button green"><span>&times;</span>Kein Versand, nur Import</a></div>';
				$html .= '</p>';
								
			}


			$html .= '<p><h2>'.$partner_id.' - '.$partnerconfig['name'].' (Re.Nr.: '.$faktura_id.') Nettobetrag:<span class="pricetag"> '.number_format($amounts['billing_amount']['EUR'], 2, ',', '.').' &euro;</span></h2>';

			$html .= '<p><label class="textinput">Navigation</label>';
			$html .= '<div class="browser" style="width: 6em;"><a href="'.$thisfile.'?job='.$prev_job.'">&lsaquo;&lsaquo; zur&uuml;ck</a></div>';
			
			if($next_job) {
				$html .= '<div class="browser" style="width: 6em;"><a href="'.$thisfile.'?job='.$next_job.'">weiter &rsaquo;&rsaquo;</a></div>';
			}

			if(($faktura_id >= 1000000) && ($faktura_id < 2000000 )) {
				$salesmodel = 'Proforma';
			} else if(($faktura_id >= 2000000) && ($faktura_id < 3000000 )) {
				$salesmodel = 'Reseller';			
			} else if(($faktura_id >= 3000000) && ($faktura_id < 4000000 )) {
				$salesmodel = 'Agency';
			} else if(($faktura_id >= 4000000) && ($faktura_id < 5000000 )) {
				$salesmodel = 'Reseller';
			} else if(($faktura_id >= 5000000) && ($faktura_id < 6000000 )) {
				$salesmodel = 'Service';
			} else if(($faktura_id >= 8000000) && ($faktura_id < 9000000 )) {
				$salesmodel = 'Agency';
			}



			if($partnerconfig['partnertype'] == 'Kreditor' && $partnerconfig['billing'] == 'Rechnung') {
				$billingtype = 'Kreditor';			
				$billingtype_class = 'kpi orange';		
			} else {
				$billingtype = 'Pubbles';
				$billingtype_class = 'kpi';		
			}
			

			$html .= '<p><label class="textinput">Allgemeines</label></p>';
			
			$html .= '<div class="kpi_group">';
		
			$html .= '<div class="kpi" style="width: 10em;"><span class="info">Abrechnungszeitraum</span><span class="value">'.$salesperiod_start.' - '.$salesperiod_end.'</span></div>';
			
			$html .= '<div class="kpi" style="width: 9em;"><span class="info">EVN Zeitstempel</span><span class="value">'.$file_timestamp.'</span></div>';

			$html .= '<div class="kpi" style="width: 4em;"><span class="info">Abrechnungsmodell</span><span class="value">'.$salesmodel.'</span></div>';
			
			// Verteilung der Abrechnungswerte auf die verschiedenene Abrechnungsmodelle
			foreach($amounts['salesmodel_billing_amount'] as $salesmodel => $salesmodel_biling_amount) {
				
				
				$ratio = round(100*$salesmodel_biling_amount/$amounts['billing_amount']['EUR'],0);

				if($ratio == 0) {
					$salesmodel_biling_amount_class = 'kpi grey';
				} else if($ratio == 100) {
					$salesmodel_biling_amount_class = 'kpi green';				
				} else {
					$salesmodel_biling_amount_class = 'kpi orange';				
				}
				$html .= '<div class="'.$salesmodel_biling_amount_class.'" style="width: 3em;"><span class="info">'.$salesmodel.'</span>'.$ratio.'%</div>';
						
			}

			$html .= '<div class="'.$billingtype_class.'" style="width: 4em;"><span class="info">Abrechnung durch</span><span class="value">'.$billingtype.'</span></div>';
			$html .= '</div>';


			$html .= '<p><label class="textinput">Informationen</label>';
			$html .= '<div class="kpi_group">';

			if($partnerconfig['partnertype'] == 'Kreditor') {
			
				//$html .= '<div class="kpi" style="width: 5em;"><span class="info">&empty; Rabatt</span><span class="value">'.(100-round((100*$amounts['billing_amount']['EUR']/$amounts['net_unit_price']['EUR']), 1)).'%</span></div>';
				
				$html .= '<div class="kpi" style="width: 5em;"><span class="info">&empty; Rabatt</span><span class="value">'.(100-$amounts['kpi']['kondition']).' %</span></div>';
				
				
				
			} else {

				//$html .= '<div class="kpi" style="width: 5em;"><span class="info">&empty; Kondition</span><span class="value">'.round(100*$amounts['billing_amount']['EUR']/$amounts['net_unit_price']['EUR'], 1).'%</span></div>';
				
				$html .= '<div class="kpi" style="width: 5em;"><span class="info">&empty; Kondition</span><span class="value">'.$amounts['kpi']['kondition'].' %</span></div>';			
				
			}


			$stornoquote = $amounts['kpi']['transaction_count']['Storno']/$amounts['kpi']['transaction_count']['Einzelkauf'];

			if($stornoquote >= 0.02) {
				$storno_class = 'red';
			} else if ($stornoquote < 0.02 && $stornoquote > 0.005) {
				$storno_class = 'orange';
			} else {
				$storno_class = 'green';
			}
			

			//$html .= '<div class="kpi" style="width: 5em;"><span class="info">&empty; Preis (netto)</span><span class="value">'.round($amounts['net_unit_price']['EUR']/$amounts['transaction_count']['Einzelkauf'],2).' &euro;</span></div>';
			
			$html .= '<div class="kpi" style="width: 5em;"><span class="info">&empty; Preis (netto)</span><span class="value">'.$amounts['kpi']['avg_net_price'].' &euro;</span></div>';

			//$html .= '<div class="kpi" style="width: 4em;"><span class="info"># Transaktionen</span>'.number_format($amounts['transaction_count']['Einzelkauf'], 0, ',', '.').'</div>';
			
			$html .= '<div class="kpi" style="width: 4em;"><span class="info"># Transaktionen</span>'.number_format($amounts['kpi']['transaction_count']['Einzelkauf'], 0, ',', '.').'</div>';

			if($stornoquote > 0) {
				$html .= '<span class="tooltip">';
			}

			$html .= '<div class="kpi '.$storno_class.'" style="width: 4em;"><span class="info">Stornoquote</span>'.round(100*$stornoquote,2).'%</div>';

			if($stornoquote > 0) {
				$html .= createTransactionInfobox($amounts['storno_transactions'], 'csv_storno').'</span>';
			}


			foreach($amounts['country_billing_amount'] as $country => $country_biling_amount) {
				
				if($country_biling_amount == 0) {
					$country_biling_amount_class = 'kpi grey';
				} else {
					$country_biling_amount_class = 'kpi';				
				}
				$html .= '<div class="'.$country_biling_amount_class.'" style="width: 3em;"><span class="info">'.$country.'</span>'.round(100*$country_biling_amount/$amounts['billing_amount']['EUR'],0).'%</div>';
						
			}					
			$html .= '</div>';

			$html .= '<p><label class="textinput">&sum; Betr&auml;ge</label>';
			$html .= '<div class="kpi_group">';
			$html .= '<div class="small_kpi" style="width: 9em;"><span class="info">&sum; Brutto Listen-VK </span><span class="value">'.number_format($amounts['gross_list_price_sum']['EUR'], 2, ',', '.').' &euro;</span></div>';
			
			$html .= '<div class="small_kpi" style="width: 7em;"><span class="info">&sum; Nettobasispreis</span><span class="value">'.number_format($amounts['net_unit_price']['EUR'], 2, ',', '.').' &euro;</span></div>';

			$html .= '<div class="small_kpi" style="width: 7em;"><span class="info">&sum; Abrechnungsbetrag</span><span class="value">'.number_format($amounts['billing_amount']['EUR'], 2, ',', '.').' &euro;</span></div>';

			$html .= '<div class="small_kpi grey" style="width: 7em;"><span class="info">davon Storno</span><span class="value">'.number_format($amounts['storno_billing_amount']['EUR'], 2, ',', '.').' &euro;</span></div>';

//flo($amounts);
//die();
			$html .= '<div class="small_kpi grey" style="width: 7em;"><span class="info">davon Verkauf</span><span class="value">'.number_format($amounts['einzelkauf_billing_amount']['EUR'], 2, ',', '.').' &euro;</span></div>';

			$html .= '</div>';

			$html .= '<p><label class="textinput">Shop-Frontend</label>';


			$html .= '<div class="kpi_group">';

			foreach($amounts['shopchannel_net_unit_price'] as $shop_channel => $sum) {

				$html .= '<div class="small_kpi" style="width: 9em;"><span class="info">'.$shop_channel.'</span><span class="value">'.number_format($sum, 2, ',', '.').' &euro;</span></div>';

			}
			$html .= '</div>';

			$html .= '<p><label class="textinput">Zeitraum / Zeitzonen</label>';
			$html .= '<div class="kpi_group">';

			if($mode != 'proforma' && ($amounts['dates']['first_transaction'] < $salesperiod_start_timestamp)) {
				$first_transaction_class = 'small_kpi orange';
			} else {
				$first_transaction_class = 'small_kpi green';
			}
			$html .= '<div class="'.$first_transaction_class.'" style="width: 8em;"><span class="info">erste Transaktion</span><span class="value">'.date('d.m.Y G:i:s', $amounts['dates']['first_transaction']).'</span></div>';

//			if($mode != 'proforma' && ($amounts['dates']['last_transaction'] > $salesperiod_end_timestamp)) {
			if($mode != 'proforma' && ($amounts['dates']['last_transaction'] > $salesperiod_end_timestamp OR date('Y-m-d', $amounts['dates']['last_transaction']) < date('Y-m-d', $salesperiod_end_timestamp))) {

				$last_transaction_class = 'small_kpi red';
			} else {
				$last_transaction_class = 'small_kpi green';
			}			
			$html .= '<div class="'.$last_transaction_class.'" style="width: 8em;"><span class="info">letzte Transaktion</span><span class="value">'.date('d.m.Y G:i:s', $amounts['dates']['last_transaction']).'</span></div>';


			foreach($amounts['timezones']['Einzelkauf'] as $timezone => $transactioncount) {
				
				$class = false;

				$ratio = $transactioncount/$amounts['kpi']['transaction_count']['Einzelkauf'];
				
				if($ratio == 1 OR $ratio == 0) {
					$class = 'small_kpi green';
				} else {
					$class = 'small_kpi orange';
				}
				$html .= '<div class="'.$class.'" style="width: 4em;"><span class="info">'.$timezone .'</span><span class="value">'.round(100*$ratio,2).' %</span></div>';
			}


			foreach($amounts['exchangerates'] as $currency => $exchangerates) {
				
				$class = false;

				//flo($amounts['exchangerates']);

				// Find duplicate Exchangerates within one Period
				if(count($exchangerates) > 1) {
					//$class = 'small_kpi orange';
				} else {
					$class = 'small_kpi green';
				}

				foreach($exchangerates as $i => $exchangerate) {

//					//$correct_rate = getExchangeRate($period, $currency);
					$correct_rate = getExchangeRate($i, $currency); 


					//flo($i);

					if($correct_rate != str_replace(',','.',$exchangerate)) {
						$class = 'small_kpi orange';
					} else {
						$class = 'small_kpi green';
					}
					$html .= '<div class="'.$class.'" style="width: 4em;"><span class="info">'.$currency.' ('.$i.')</span><span class="value">'.$exchangerate.'</span></div>';
				}
				


			}
			$html .= '</div>';

			$html .= '<p><label class="textinput">Preisvergleiche</label>';
			$html .= '<div class="kpi_group">';

			if($amounts['actual_gross_price']['EUR'] > 0) {
				
				$total_price_delta_eur_rel = round(($amounts['actual_gross_price']['EUR']-$amounts['gross_list_price']['EUR'])/$amounts['actual_gross_price']['EUR']*100,2);
				
				$price_delta_class = 'small_kpi';
				
				if(abs($total_price_delta_eur_rel) <= 0.5) {
					$price_delta_class = 'small_kpi green';	
				} else if (abs($total_price_delta_eur_rel) > 0.5 && abs($total_price_delta_eur_rel) <= 1) {
					$price_delta_class = 'small_kpi orange';	
				} else if (abs($total_price_delta_eur_rel) > 1) {
					$price_delta_class = 'small_kpi red';	
				}
			} else {
				$total_price_delta_eur_rel = '---';
				$price_delta_class = 'small_kpi grey';	

			}

//			flo($amounts['gross_list_price']);
	
			$html .= '<div class="small_kpi grey" style="width: 7em;"><span class="info">&sum; Brutto Listen-VK EUR</span><span class="value">'.number_format($amounts['gross_list_price']['EUR'], 2, ',', '.').' <span class="small">&euro;</span></span></div>';
			
			$html .= '<div class="'.$price_delta_class.'" style="width: 7em;"><span class="info">&sum; Actual Price EUR</span><span class="value">'.number_format($amounts['actual_gross_price']['EUR'], 2, ',', '.').' <span class="small">&euro;</span></span></div>';
			
			//$html .= '<div class="small_kpi grey" style="width: 5em;"><span class="info">Delta EUR</span><span class="value">'.number_format($amounts['actual_gross_price']['EUR']-$amounts['gross_list_price']['EUR'], 2, ',', '.').' <span class="small">&euro;</span></span></div>';
			
			$html .= '<div class="'.$price_delta_class.'" style="width: 5em;"><span class="info">Delta %</span><span class="value">'.$total_price_delta_eur_rel.' <span class="small">%</span></span></div>';
		
			//$html .= '<div class="small_kpi grey" style="width: 7em;"><span class="info">&sum; Brutto Listen-VK EUR</span><span class="value">'.number_format($amounts['gross_list_price']['EUR'], 2, ',', '.').' <span class="small">%</span></span></div>';

			if($amounts['actual_gross_price']['CHF'] > 0) {
				$total_price_delta_chf_rel = round(($amounts['actual_gross_price']['CHF']-$amounts['gross_list_price']['CHF'])/$amounts['actual_gross_price']['CHF']*100,2);
		
				$price_delta_class = 'small_kpi';
				
				if(abs($total_price_delta_chf_rel) <= 0.5) {
					$price_delta_class = 'small_kpi green';	
				} else if (abs($total_price_delta_chf_rel) > 0.5 && abs($total_price_delta_chf_rel) <= 1) {
					$price_delta_class = 'small_kpi orange';	
				} else if (abs($total_price_delta_chf_rel) > 1) {
					$price_delta_class = 'small_kpi red';	
				}

			} else {
				$total_price_delta_chf_rel = '---';
				$price_delta_class = 'small_kpi grey';	

			}
			
			

			
			
			$html .= '<div class="small_kpi grey" style="width: 7em;"><span class="info">&sum; Brutto Listen-VK CHF</span><span class="value">'.number_format($amounts['gross_list_price']['CHF'], 2, ',', '.').' <span class="small">CHF</span></span></div>';

			$html .= '<div class="'.$price_delta_class.'" style="width: 7em;"><span class="info">&sum; Actual Price CHF</span><span class="value">'.number_format($amounts['actual_gross_price']['CHF'], 2, ',', '.').' <span class="small">CHF</span></span></div>';
		
			$html .= '<div class="'.$price_delta_class.'" style="width: 5em;"><span class="info">Delta %</span><span class="value">'.$total_price_delta_chf_rel.' <span class="small">%</span></span></div>';
			$html .= '</div>';

			if((count($amounts['transactions_with_pricediff_low']) > 0) OR (count($amounts['transactions_with_pricediff_high']) > 0)) {

				$html .= '<p><label class="textinput">Preiswarnungen</label></p>';
				
				$counter = count($amounts['transaction_count_free_with_charge']);

				$counter_high = 0;
				$counter_low = 0;



				$counter_high = count($amounts['transactions_with_pricediff_low']);
				$counter_low = count($amounts['transactions_with_pricediff_high']);

				if($counter_high > 0) {

					$above_price_ratio = round(100*$counter_high/$amounts['kpi']['transaction_count']['Einzelkauf'], 2);

					$html .= '<span class="tooltip"><div class="kpi orange" style="width: 6em;"><span class="info">unter Listen-VK</span>'.$above_price_ratio.'<span class="small">% ('.$counter_high.')</span></div>'.createTransactionInfobox($amounts['transactions_with_pricediff_low'], 'csv_pricediff_low', 'ISBN').'</span>';
					//$html .= '<span class="tooltip"><div class="kpi grey" style="width: 4em;"><span class="info">unter Listen-VK</span>'.$counter_high.'</div>'.createTransactionInfobox($amounts['transactions_with_pricediff_low'], 'csv_pricediff_low', 'ISBN').'</span>';
				}

				if($counter_low > 0) {
					$below_price_ratio = round(100*$counter_low/$amounts['kpi']['transaction_count']['Einzelkauf'], 2);

					$html .= '<span class="tooltip"><div class="kpi orange" style="width: 6em;"><span class="info">&uuml;ber Listen-VK</span>'.$below_price_ratio.'<span class="small">% ('.$counter_low.')</span></div>'.createTransactionInfobox($amounts['transactions_with_pricediff_high'], 'csv_pricediff_high', 'ISBN').'</span>';
					//$html .= '<span class="tooltip"><div class="kpi grey" style="width: 4em;"><span class="info">&uuml;ber Listen-VK</span>'.$counter_low.'</div>'.createTransactionInfobox($amounts['transactions_with_pricediff_high'], 'csv_pricediff_high', 'ISBN').'</span>';
				}
				
			}


			if(count($amounts['transaction_count_free_with_charge']) > 0) {
				
				$html .= '<p><label class="textinput">Free with Charge</label></p>';
	
				$counter = count($amounts['transaction_count_free_with_charge']);

				foreach($amounts['transaction_count_free_with_charge'] as $domain => $transactions_ids) {
					$html .= '<span class="tooltip"><div class="small_kpi red" style="width: 4em;"><span class="info">'.$domain.'</span>'.count($transactions_ids).'</div>'.createTransactionInfobox($transactions_ids, 'csv_freewithcharge').'</span>';
				}				
				
			}

			if(1 OR $partnerconfig['partnertype'] == 'Kreditor') {
				$html .= '<p><label class="textinput">Vertriebskan&auml;le</label>';

				$html .= '<div class="kpi_group">';
				
				foreach($amounts['distributor_billing_amount'] as $domain => $distributor_billing_amount) {

					if($distributor_billing_amount > 0) {
						if($domain == 'unbekannt') {
							$distributor_billing_amount_class = 'small_kpi red';
						} else {
							$distributor_billing_amount_class = 'small_kpi';
						}
					} else {
						$distributor_billing_amount_class = 'small_kpi grey';
					}

					$html .= '<div class="'.$distributor_billing_amount_class.'" style="width: 3em;"><span class="info">'.$domain.'</span>'.round(100*$distributor_billing_amount/$amounts['billing_amount']['EUR'], 1).'%</div>';
				}
			}
			
			$html .= '</div>';

			$html .= '<p><label class="textinput">Sitz der Endkunden</label></p>';

			$html .= '<div class="kpi_group">';
			
			arsort($amounts['customer_country_billing_amount']);
			
			$i = 0;
			foreach($amounts['customer_country_billing_amount'] as $country => $customer_country_billing_amount) {
			
				if($i < 10) {
					
					if($customer_country_billing_amount > 0) {
						$customer_country_billing_amount_class = 'small_kpi';
					} else {
						$customer_country_billing_amount_class = 'small_kpi grey';
					}
				
					$html .= '<div class="'.$customer_country_billing_amount_class.'" style="width: 3em;"><span class="info">'.$country.'</span>'.round(100*$customer_country_billing_amount/$amounts['billing_amount']['EUR'], 0).'%</div>';
				} else if ($i == 10){
					
					$html .= '<div class="small_kpi grey" style="width: 3em;"><span class="info">...</span>...</div>';
											
				}
				$i++;
			}

			$html .= '</div>';
			if($mode == 'view' OR $mode == 'proforma') {


				$res = cacheBillingData($amounts, $dir, $path);
/*				
				$string = serialize($amounts);

				if($path == 'sent') {
					$targetfile = $DIR_070_SENT_FILES.$dir.'/billingdata.cache';
				} else {
					$targetfile = $DIR_060_IMPORTED_FILES.$dir.'/billingdata.cache';
				}

				file_put_contents($targetfile, $string);
*/
			}

			$html .= '<p><label class="textinput">Dateien</label><div id="attachments"><ol>';





			foreach($mail['files'] as $k => $file) {

				if($path == 'sent') {
					$attachmentfile = $DIR_070_SENT_FILES.$dir.'/'.$file;
				} else {
					$attachmentfile = $DIR_060_IMPORTED_FILES.$dir.'/'.$file;
				}

				$html .= '<li><a href="showfile.php?status='.$path.'&file='.$dir.'/'.$file.'" target="_blank">'.$file.'</a> (Größe '.size_readable(filesize($attachmentfile)).')</li>';

				if($path == 'sent') {
					$parts = explode('.',$file);
					
					if($parts[count($parts)-1] == 'pdf') {

//						$html .= '<li><img src="pdfimage.php?status='.$path.'&file='.$dir.'/'.$file.'" width="300"></li>';


/*
						$html .= <<<EOT

<script>
PDFJS.getDocument('helloworld.pdf').then(function(pdf) {
  // Using promise to fetch the page
  pdf.getPage(1).then(function(page) {
    var scale = 1.5;
    var viewport = page.getViewport(scale);

    //
    // Prepare canvas using PDF page dimensions
    //
    var canvas = document.getElementById('the-canvas');
    var context = canvas.getContext('2d');
    canvas.height = viewport.height;
    canvas.width = viewport.width;

    //
    // Render PDF page into canvas context
    //
    var renderContext = {
      canvasContext: context,
      viewport: viewport
    };
    page.render(renderContext);
  });
});

</script>
EOT;
*/


//						$html .= '<li><canvas id="the-canvas" style="border:1px solid black;"/></li>';


						
					}
					
					
				}


			}
			$html .= '</ol></div></p>';

			if($path != 'sent' AND $mode != 'proforma') {


				
				$html .= '<div id="form_container"><form id="form" action="send.php?action=send&job='.$dir.'" method="POST">';
				$html .= '<p><label class="textinput">An</label><input id="mail_to" type="text" name="mailvalues[to]" value="'.$mail['to'].'"/></p>';
				$html .= '<p><label class="textinput">Von E-Mail</label><input id="mail_from_mail" type="text" name="mailvalues[from_mail]" value="'.$mail['from_mail'].'"/></br>';
				$html .= '<p><label class="textinput">Von Name</label><input id="mail_from_name" type="text" name="mailvalues[from_name]" value="'.$mail['from_name'].'"/></br>';
				$html .= '<p><label class="textinput">Bcc</label><input id="mail_bcc" type="text" name="mailvalues[bcc]" value="'.$mail['bcc'].'"/></br>';
				$html .= '<p><label class="textinput">Reply To</label><input id="mail_replyto" type="text" name="mailvalues[replyto]" value="'.$mail['replyto'].'"/></br>';
				$html .= '<p><label class="textinput">Betreff</label><input id="mail_subject" type="text" name="mailvalues[subject]" value="'.$mail['subject'].'"/></br>';
				$html .= '<p><label class="textinput">Nachricht</label><textarea rows="25" name="mailvalues[message]">'.$mail['message'].'</textarea></br>';
				//$html .= '<p><label>Dateien</label>';
	
				$html .= '';

				if($accounts[$partner_id]['tier'] > 0) {
					
					if($accounts[$partner_id]['partnertype'] == 'Kreditor') {
						$sort = 'asc';
					} else {
						$sort = 'desc';
					}
					
					$html .= '<p><label class="textinput">Top 30 Verlage</label>'.createTransactionInfobox($amounts['pivots']['publisher'], 'csv_storno', 'Publisher', false, $sort);								

				}
	

				if($ALLOW_IMPORT_ONLY) {
	
					$html .= '<p><label class="textinput"><label>';
					$html .= '<div class="container"><a href="importfaktura.php?job='.$dir.'" class="button green"><span>&times;</span>Kein Versand, nur Import</a></div>';
					$html .= '</p>';
									
				}

				$html .= '<p><label class="textinput"><label>';
				$html .= '<div class="container"><a href="javascript:{}" onclick="document.getElementById(\'form\').submit();" class="button"><span>&#10003;</span>Versand starten</a></div>';
				$html .= '<div class="container"><a href="deletemail.php?job='.$dir.'" class="button red"><span>&times;</span>Mail l&ouml;schen</a></div>';
				$html .= '</p>';
	
				$html .= '</div></form>';	
			}	
			
			if(($mode == 'view' && $path == 'sent') OR $mode == 'proforma') {


				if(count($amounts['transactions_with_pricediff_low'])) {
					$html .= '<p><label class="textinput">Unter Listen-VK</label>'.createTransactionInfobox($amounts['transactions_with_pricediff_low'], 'csv_pricediff_low', 'ISBN', false);
				}
				if(count($amounts['transactions_with_pricediff_high'])) {
					$html .= '<p><label class="textinput">&Uuml;ber Listen-VK</label>'.createTransactionInfobox($amounts['transactions_with_pricediff_high'], 'csv_pricediff_high', 'ISBN', false);
				}
			
				if(isset($transactions_ids)) {
					$html .= '<p><label class="textinput">Free with charge</label>'.createTransactionInfobox($transactions_ids, 'csv_freewithcharge', 'ISBN', false);				
				}
				
				if(count($amounts['storno_transactions']) > 0) {
					$html .= '<p><label class="textinput">Stornos</label>'.createTransactionInfobox($amounts['storno_transactions'], 'csv_storno', 'Reason for Cancellation', false);				
				}
				
				if(count($amounts['storno_transactions']) > 0) {
					$html .= '<p><label class="textinput">Stornos nach Typen</label>'.createTransactionInfobox($amounts['storno_transactions'], 'csv_storno_types', 'Stornotypes', false);				
				}				

				//if($accounts[$partner_id]['tier'] > 1) {
					if($accounts[$partner_id]['partnertype'] == 'Kreditor') {
						$sort = 'asc';
					} else {
						$sort = 'desc';
					}
					$html .= '<p><label class="textinput">Top 30 Verlage</label>'.createTransactionInfobox($amounts['pivots']['publisher'], 'csv_storno', 'Publisher', false, $sort);								
					$html .= '<p><label class="textinput">Top 25 Produkte</label>'.createTopProductsTable($amounts['pivots']['products'], 'desc', 25);


				//}
			}
			
			if($mode == 'proforma') {
				$html .= '<p><label class="textinput"><label><div class="container"><a href="deletemail.php?job='.$dir.'" class="button red"><span>&times;</span>Proforma EVN l&ouml;schen</a></div></p><div style="clear:both;"></div>';
			} 
			
			//print($html);
			return $html;
				
		} else if($mode == 'send') {
			
			if(!mail_attachment($mail, $preview)) {
				errorlog('Mail an '.$mail['to'].' "'.$mail['subject'].'" konnte nicht versendet werden!');
			} else {

				$newdirname = $DIR_070_SENT_FILES.$period.'_'.$partner_id.'_'.$faktura_id.'/';

				$html = '';
				$html .= '<p><label class="textinput"><label>';
				$html .= '<div class="container"><a href="send.php?job=next" class="button green"><span>&times;</span>Weiter</a></div>';
				$html .= '</p>';
				print($html);

				//die('NOT DELETED FOR TESTING PURPOSES!');

				if (!dirmv($mail['path'], $newdirname, true, '')) {
				    errorlog('Verzeichnis '.$mail['path'].' konnte nicht umbenannt werden!');
				    return;
				}
				

	
			}
		
		} else if($mode == 'import_no_send') {

		    die('Aktion unklar, mode nicht gesetzt!');

		} else {

		    die('Aktion unklar, mode nicht gesetzt!');
			
		}
}


function mail_attachment($mail, $preview) {
	
	global $SEND_EMAILS; 
	
	$phpmail = new PHPMailer;
	

	/*
	$phpmail->IsSMTP();  

       // Set mailer to use SMTP
	$phpmail->Host = 'mail.former03.de';  // Specify main and backup server
	$phpmail->SMTPAuth = true;                               // Enable SMTP authentication
	$phpmail->Username = 'info@mailfactory.pubbles.de';                            // SMTP username
	$phpmail->Password = 'Pubblmail!8479';                           // SMTP password
	*/

	$phpmail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted

	$phpmail->From = $mail['from_mail'];
	$phpmail->FromName = $mail['from_name'];
	$phpmail->ReturnPath = $mail['from_mail'];
	$phpmail->Sender = $mail['from_mail'];

	// Email priority (1 = High, 3 = Normal, 5 = low).
	$phpmail->Priority = 1;

	$recipients = explode(",", $mail['to']);
	foreach($recipients as $k => $recipient) {
		$phpmail->AddAddress($recipient);  // Add a recipient
	}

 	$phpmail->AddReplyTo($mail['replyto']);

	$bcc_recipients = explode(",", $mail['bcc']);
	foreach($bcc_recipients as $k => $bcc_recipient) {
		$phpmail->AddBCC($bcc_recipient);  // Add a recipient
	}

	$phpmail->WordWrap = 50;                                 // Set word wrap to 50 characters

	foreach($mail['files'] as $k => $file) {
		$phpmail->AddAttachment($mail['path'].$file);  // Add a recipient
	}

	$phpmail->AddAttachment('/var/tmp/file.tar.gz');         // Add attachments
	$phpmail->AddAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
	$phpmail->IsHTML(true);                                  // Set email format to HTML
	
	$phpmail->Subject = $mail['subject'];
	$phpmail->Body    = $mail['message'];
	//$phpmail->AltBody = $mail['message'];
		
	if($preview) {
			echo'<h2>E-Mail Vorschau</h2>';

			echo'<pre>';
			print_r($mail);
			echo'</pre>';
			
	} else {
		if($SEND_EMAILS) {
		
			$res = $phpmail->Send();
			if(!$res) {

				echo usermessage('error', $phpmail->ErrorInfo);
				errorlog('Mailer Error: ' . $phpmail->ErrorInfo);
		
			} else {
			
				//echo'<pre>';
				//print_r($mail);
				//echo'</pre>';
				$success = 'Mailversand erfolgt (Betreff: '.$mail['subject'].')';
				echo usermessage('success', $success);
				actionlog($success);
	
			}
		} else {
			echo'<h2>E-Mail Vorschau ($SEND_EMAILS = false)</h2>';
			echo'<pre>';
			print_r($mail);
			echo'</pre>';
			
			$test = 'Test-Mailversand erfolgt (Betreff: '.$mail['subject'].')';
			echo usermessage('info', $test);
			actionlog($test);
	
			$res = true;
		}
	} 
		
	return ($res);
	
}


function createTopProductsTable($transactions, $sort = 'desc', $limit = 50) {
	global $accounts; 

	arsort($transactions);



	if(count($transactions > 0)) {


		$html = '';

		$html .= '<table class="transaction_infobox"  border="1">';
		
		$i = 0;

		foreach($transactions as $key => $values) {
			
			if($limit > 0 && $i > $limit) {
				continue;
			}
			
			
			foreach($values as $foo => $bar) {
				$values[$foo] = $bar;

			}
			

			unset($values['prices']);



			if($i == 0) {

				$html .= '<tr class="head">'; 		

				$html .= '<td colspan="7"></td>';
	
				foreach(array_keys($values) as $k => $columnheader) {

					if($columnheader == 'distribution_channels') {

						foreach($values[$columnheader] as $distributor_id => $sub_colums) {

//							$html .= '<td colspan="1"><div class="verticalText">'.$accounts[$distributor_id]['name'].'</div></td>';
							$html .= '<td colspan="1"><div class="verticalText">'.$accounts[$distributor_id]['name'].'</div></td>';

						}



					} else {

					}
				}
				$html .= '</td></tr>'; 	


				$html .= '<tr class="head">'; 		

				$html .= '<td>#</td>';
				$html .= '<td>ISBN</td>';
				$html .= '<td>Titel</td>';
				$html .= '<td>Autor</td>';
				$html .= '<td>Publisher</td>';
				//$html .= '<td>Salesmodel</td>';
				$html .= '<td>Nettoumsatz</td>';
				$html .= '<td>#</td>';

				foreach(array_keys($values) as $k => $columnheader) {


					if($columnheader == 'distribution_channels') {

						foreach($values[$columnheader] as $distributor_id => $sub_colums) {

							foreach($sub_colums as $label => $value) {
								if($label == 'net_unit_price') {
									$html .= '<td>%</td>';
								} else if($label == 'count') {
									//$html .= '<td>€</td>';
								}
							}
						}
					}
				}
				$html .= '</td></tr>'; 		
	
			} 
			
		
			$html .= '<tr>'; 		

			$html .= '<td>'.$i.'</td>';
//			$html .= '<td>'.$key.'</td>';
			$html .= '<td>'.htmlentities($values['isbn']).'</td>';
			$html .= '<td>'.htmlentities($values['title']).'</td>';
			$html .= '<td>'.htmlentities($values['author']).'</td>';
			$html .= '<td>'.htmlentities($values['publisher']).'</td>';
			//$html .= '<td>'.$values['salesmodel'].'</td>';
			$html .= '<td>'.number_format($values['net_unit_price_sum'], 2, ',', '.').' €</td>';
			$html .= '<td>'.$values['count'].'</td>';


			$net_unit_price_sum = $values['net_unit_price_sum'];
			
			foreach(array_keys($values) as $k => $columnheader) {

				if($columnheader == 'distribution_channels') {

					foreach($values[$columnheader] as $distributor_id => $sub_colums) {

						foreach($sub_colums as $label => $value) {

							if($label == 'net_unit_price') {
							
								if($net_unit_price_sum == 0 OR $value == 0) {

									$html .= '<td></td>';
								
								} else {

									$html .= '<td class="">'.round(100*$value/$net_unit_price_sum, 1).'&nbsp;%'.'</td>';
									
								}
							} else {
								//$html .= '<td>'.htmlentities($value).'</td>';
							}
						}	
					}
				}
			}
			$html .= '</tr>'; 		

			$i++;
		}
		
		$html .= '</table>';

		return $html;
	} else {
		return false;
	}
}


function createTransactionInfobox($transactions, $id, $cum = false, $textarea = true, $sort = 'asc') {
	
	if(count($transactions > 0)) {
	
		if($cum == 'ISBN') {

			$cum_transactions = array();

			foreach($transactions as $key => $values) {
				
				if(!array_key_exists($values['ISBN'], $cum_transactions)) {

					$cum_transactions[$values['ISBN']]['count'] = 0;
					$cum_transactions[$values['ISBN']]['ISBN'] = $values['ISBN'];
					$cum_transactions[$values['ISBN']]['Supplier'] = $values['Supplier'];
					$cum_transactions[$values['ISBN']]['Distribution Channel'] = $values['Distribution Channel'];
					$cum_transactions[$values['ISBN']]['Sales Model'] = $values['Sales Model'];
					$cum_transactions[$values['ISBN']]['Title'] = $values['Title'];
					$cum_transactions[$values['ISBN']]['Actual Gross Price'] = $values['Actual Gross Price'];
					$cum_transactions[$values['ISBN']]['Actual Gross Price Currency'] = $values['Actual Gross Price Currency'];
					$cum_transactions[$values['ISBN']]['Gross List Price'] = $values['Gross List Price'];
					$cum_transactions[$values['ISBN']]['Gross List Price Currency'] = $values['Gross List Price Currency'];


					$cum_transactions[$values['ISBN']]['pricediff'] = 0;
				}

				$cum_transactions[$values['ISBN']]['count']++;
				
				if(array_key_exists('pricediff', $values)) {
					$cum_transactions[$values['ISBN']]['pricediff'] = $cum_transactions[$values['ISBN']]['pricediff']+$values['pricediff'];
				}
			}
			$transactions = $cum_transactions;

		} else if($cum == 'Reason for Cancellation') {

			$cum_transactions = array();
					
			foreach($transactions as $key => $values) {
				
				if(!array_key_exists($values['Reason for Cancellation'], $cum_transactions)) {

					$cum_transactions[$values['Reason for Cancellation']]['Reason for Cancellation'] = $values['Reason for Cancellation'];
					$cum_transactions[$values['Reason for Cancellation']]['count'] = 0;
					//$cum_transactions[$values['Reason for Cancellation']]['ISBN'] = $values['ISBN'];
					//$cum_transactions[$values['Reason for Cancellation']]['Supplier'] = $values['Supplier'];
				}

				$cum_transactions[$values['Reason for Cancellation']]['count']++;
				
			}
			$transactions = $cum_transactions;
			
		} else if($cum == 'Stornotypes') {

			$cum_transactions = array();
					
			foreach($transactions as $key => $values) {

				$type = $values['Transaction Type'];
			
				if(!array_key_exists($type, $cum_transactions)) {

					$cum_transactions[$type]['Transaction Type'] = $values['Transaction Type'];
					$cum_transactions[$type]['count'] = 0;
					//$cum_transactions[$type]['ISBN'] = $values['ISBN'];
					//$cum_transactions[$type]['Supplier'] = $values['Supplier'];
					$cum_transactions[$type]['Billing Amount'] = 0;					
				}
				$cum_transactions[$type]['Billing Amount'] += str_replace(',','.',($values['Billing Amount']));
								
				$cum_transactions[$type]['count'] ++;
			}
			$transactions = $cum_transactions;

		} else if($cum == 'Publisher') {

			$cum_transactions = array();
				
			$i = 0;	
			foreach($transactions as $key => $values) {

				//flo($values);			

				if(!array_key_exists($i, $cum_transactions)) {
					$cum_transactions[$i] = array();
					$cum_transactions[$i]['billing_amount'] = 0;	
					$cum_transactions[$i]['count'] = 0;
					$cum_transactions[$i]['publisher'] = false;
					$cum_transactions[$i]['supplier'] = false;
				}

				$cum_transactions[$i]['billing_amount'] += $values['billing_amount'];	
				$cum_transactions[$i]['count'] = $values['count'];
				$cum_transactions[$i]['publisher'] = $values['publisher'];
				$cum_transactions[$i]['supplier'] = $values['supplier'];
				$i++;

		
			}
			
			if($sort == 'asc') {
				asort($cum_transactions);						
			} else {
				arsort($cum_transactions);			
			}
			
			//flo($cum);
			
			$transactions = array();

			$i = 0;
			foreach($cum_transactions as $k => $v) {

				if($i < 30) {
					$ar = array();
					$ar['Rang'] = $i+1;
					$ar['Publisher'] = $v['publisher'];
					$ar['Supplier'] = $v['supplier'];
					$ar['Anzahl Transaktionen'] = number_format($v['count'],0,',','.');
					$ar['Billing Amount'] = number_format($v['billing_amount'],2,',','.').' EUR';
					$i++;	
					$transactions[] = $ar;
				}
			}



			//$transactions = $sorted_transactions;
			//flo($transactions);
		}



		$csv = '';
		$html = '<em>';
		$html .= '<table class="transaction_infobox"  border="1">';
		
		$i = 0;
		foreach($transactions as $key => $values) {
			
			foreach($values as $foo => $bar) {
				$values[$foo] = htmlentities($bar);
			}
			
			$csv_values = $values;

			if($i == 0) {

				$html .= '<tr class="head">'; 		
	
				foreach(array_keys($values) as $i => $columnheader) {
					$html .= '<td class="'.strtolower(str_replace(' ', '_', $columnheader)).'">'.$columnheader.'</td>';
				}
				$html .= '</td></tr>'; 		

				//$html .= '<tr><td>'.implode("</td><td>", array_keys($values)).'</td></tr>'; 		
				$csv .= '"'.implode('"	"', array_keys($values)).'"'."\n"; 	
	
			} 
			
			if(array_key_exists('Distribution Channel', $values)) {
				$values['Title'] = linkTransaction($values['ISBN'], $values['Title'], $values['Distribution Channel']);
			}

			$html .= '<tr class="trans"><td>'.implode("</td><td>", $values).'</td></tr>'; 		
			$csv .= '"'.implode('"	"', $csv_values).'"'."\n"; 	
			$i++;
		}

//		$html .= '</table><div id="'.$id.'" style="display: ;">'.$csv.'</div></em>';

		$html .= '</table>';
		
		if($textarea !== false) {
			$html .= '<textarea id="'.$id.'" style="float: left;">'.$csv.'</textarea>';
		}
		$html .='</em>';

		//arsort($cum_transactions);

		//print_r($cum_transactions, 'count');
						
		return $html;
	} else {
		return false;
	}
}


function getArticlepageUrl($isbn, $domain) {

	$isbn = str_replace('-', '', $isbn);
	
	switch($domain) {
		case 'langenscheidt.de':
		
			$isbnparts = array();
			$isbnparts[1] = substr($isbn,0,3);
			$isbnparts[2] = substr($isbn,3,1);
			$isbnparts[3] = substr($isbn,4,3);
			$isbnparts[4] = substr($isbn,7,5);
			$isbnparts[5] = substr($isbn,12,1);

			$dashed_isbn = implode('-', $isbnparts);

			
//			$url = 'http://www.'.$domain.'/suche?s='.$isbn.'';
			$url = 'http://www.'.$domain.'/isbn/'.$dashed_isbn.'';
			
			
			break;
			
		case 'buchkatalog.de':
			// http://www.buchkatalog.de/kod-bin/isuche.cgi?quicksearch=9783468201288
			$url = 'http://www.'.$domain.'/kod-bin/isuche.cgi?quicksearch='.$isbn.'';
			break;
			
		case 'weltbild.de':
		case 'weltbild.at':
		case 'weltbild.ch':
			$url = 'http://www.'.$domain.'/9/'.$isbn.'.html';
			break;
 
		case 'buecher.de':
		case 'ebooks.bild.de':
		case 'hugendubel.de':
			$url = 'http://www.'.$domain.'/isbn/'.$isbn.'';

			break;


		case 'otto-media.de':
			$url = 'http://www.'.$domain.'/om/product/standard/buch/bla/bla/bla/4/'.$isbn.'';

			break;
			
		case 'derclub.de':
//			$url = 'http://www.'.$domain.'/de/site/Search/Start?firstSearch=&categoryFilter=&volltext='.$isbn.'';
//			$url = 'http://www.'.$domain.'/de/product/standard/buch/bla/bla/bla/4/'.$isbn.'';
//			$url = 'http://www.'.$domain.'/p/4/bla/'.$isbn.'';
			$url = 'http://www.'.$domain.'/suche?volltext='.$isbn.'';
			
			break;
			
		case 'donauland.at':
			$url = 'http://www.'.$domain.'/dl/site/Search/Start?firstSearch=&categoryFilter=&volltext='.$isbn.'';
//			$url = 'http://www.'.$domain.'/dl/product/standard/buch/bla/bla/bla/4/'.$isbn.'';
						
			break;

		case 'thalia.de':
		case 'thalia.at':
		case 'thalia.ch':
			$url = 'http://www.'.$domain.'/shop/ebooks/suche/?sq='.$isbn.'';
			break;
			
		case 'buch.de':
		case 'buch.ch':
			$url = 'http://www.'.$domain.'/shop/de_ebook_start/suche/?sq='.$isbn.'';
			break;
			
		case 'amazon.de':
			$url = 'http://www.'.$domain.'/s/?field-keywords='.$isbn.'';
			break;
			
			
		case 'ebook.de':
			$url = 'http://www.'.$domain.'/de/quickSearch?searchString='.$isbn.'';
			break;
			
		case 'libreka.de':
			$url = 'http://www.'.$domain.'/'.$isbn.'';
			break;

		default: 
			$url = false;
			break;
	}

	return $url;

}		

function linkTransaction($isbn, $label, $domain, $urlonly = false) {
	
	$url = getArticlepageUrl($isbn, $domain);

	$link = '<a target="_blank" href="'.$url.'">'.$label.'</a>';
	return $link;
}






function readEVN($file, $partnerconfig) {

	global $accounts, $known_domains;

	// Zahlungseingang vs. Zahlungsausgang
	if($partnerconfig['partnertype'] == 'Kreditor') {
		$billingfactor = '-1';		
	} else {
		$billingfactor = '1';		
	}
	

	$fileparts = explode('/', $file);
	$job = $fileparts[2];

	$fileparts = explode('_', $fileparts[2]);
	$faktura_id = $fileparts[2];


	$filecontent = file($file, true);
	
	$billing_amount = 0;
	$targetcolumn = false;
	
	$values = array();

	$values['salesperiod_start'] = false;
	$values['salesperiod_end'] = false;

	$values['billing_amount']['EUR'] = 0;
	$values['storno_billing_amount']['EUR'] = 0;
	$values['einzelkauf_billing_amount']['EUR'] = 0;


	$values['net_unit_price']['EUR'] = 0;
	$values['gross_list_price']['EUR'] = 0;
	$values['gross_list_price']['CHF'] = 0;

	$values['gross_list_price_sum']['EUR'] = 0;

	
	$values['actual_gross_price']['EUR'] = 0;
	$values['actual_gross_price']['CHF'] = 0;
	
	$values['kpi']['transaction_count']['Einzelkauf'] = 0;
	$values['kpi']['transaction_count']['Storno'] = 0;
	
//	$values['transaction_count']['Einzelkauf'] = 0;
//	$values['transaction_count']['Storno'] = 0;

	$values['country_billing_amount']['DE'] = '';
	$values['country_billing_amount']['AT'] = '';
	$values['country_billing_amount']['CH'] = '';

	$values['salesmodel_billing_amount']['Reseller'] = 0;
	$values['salesmodel_billing_amount']['Agency'] = 0;
	$values['salesmodel_billing_amount']['Serviceauftrag'] = 0;

	$values['dates']['first_transaction'] = false;
	$values['dates']['last_transaction'] = false;

	$values['dates']['billing_start_date'] = false;
	$values['dates']['billing_end_date'] = false;

	$values['timezones']['Einzelkauf'] = array();

	foreach($known_domains as $k => $known_domain) {

		$values['distributor_billing_amount'][$known_domain] = 0;
		$values['distributor_net_unit_price'][$known_domain] = 0;

	}

	$values['distributor_billing_amount']['unbekannt'] = 0;
	$values['distributor_net_unit_price']['unbekannt'] = 0;

	$values['exchangerates'] = array();

	$values['customer_country_billing_amount'] = array();

	$values['pivots'] = array();
	$values['pivots']['publisher'] = array();
	$values['pivots']['author'] = array();
	$values['pivots']['supplier'] = array();
	$values['pivots']['products'] = array();


	$values['daily_amounts'] = array();
	$values['daily_amounts']['net_unit_price'] = array();
	$values['daily_amounts']['billing_amount'] = array();
	$values['daily_amounts']['actual_gross_price'] = array();

	$values['transactions_with_pricediff_high'] = array();
	$values['transactions_with_pricediff_low'] = array();

	$values['storno_transactions'] = array();

	$values['transaction_count_free_with_charge'] = array();


	$billing_amount = 0;

	$column_labels = array();
	
	
	$hidecols = array();
	$hidecols[] = 'Transaction ID';
	$hidecols[] = 'Distribution Channel ID';
	$hidecols[] = 'Quantity';
	$hidecols[] = 'Quantity Unit';
	$hidecols[] = 'Actual Tax Rate [%]';
	$hidecols[] = 'Actual Tax Amount';
	$hidecols[] = 'Actual Net Price';
	$hidecols[] = 'Author';
	$hidecols[] = 'Format';
	$hidecols[] = 'Product ID';
	$hidecols[] = 'Product Type';
	$hidecols[] = 'Regional Sales Tax';
	$hidecols[] = 'Publisher';



	$topseller = array();
		
	$columns = csvLineToArray($filecontent[0]);

	$column_count = count($columns);
	
	foreach($columns as $i => $cell) {

		$column_label[$i] = $cell;

		$trimmedvalue = trim($cell,'"');

		if($trimmedvalue == 'Billing Amount') {
			$billing_amount_col = $i;
		}

		if($trimmedvalue == 'Transaction ID') {
			$transaction_id_col = $i;
		}

		if($trimmedvalue == 'Retailer Transaction-ID') {
			$transaction_id = $i;
		}

		if($trimmedvalue == 'ISBN') {
			$isbn_col = $i;
		}

		if($trimmedvalue == 'Title') {
			$title_col = $i;
		}

		if($trimmedvalue == 'Gross List Price') {
			$gross_list_price_col = $i;
		}

		if($trimmedvalue == 'Gross List Price Currency') {
			$gross_list_price_currency_col = $i;
		}

		if($trimmedvalue == 'Publisher') {
			$publisher_col = $i;
		}
		
		if($trimmedvalue == 'Author') {
			$author_col = $i;
		}

		if($trimmedvalue == 'Supplier ID') {
			$supplier_id_col = $i;
		}

		if($trimmedvalue == 'Supplier') {
			$supplier_col = $i;
		}

		if($trimmedvalue == 'Actual Gross Price') {
			$actual_gross_price_col = $i;
		}
		
		if($trimmedvalue == 'Actual Gross Price Currency') {
			$actual_gross_price_currency_col = $i;
		}

		if($trimmedvalue == 'Net Unit Price [EUR]') {
			$net_unit_price_col = $i;
		}

		if($trimmedvalue == 'Transaction Type') {
			$salestype = $i;
		}

		if($trimmedvalue == 'Product ID') {
			$product_id_col = $i;
		}
		
		if($trimmedvalue == 'Country of Distributor') {
			$country_of_distributor_col = $i;
		}

		if($trimmedvalue == 'Distribution Channel') {
			$distributor_col = $i;
		}

		if($trimmedvalue == 'Distribution Channel ID') {
			$distributor_id_col = $i;
		}
		
		if($trimmedvalue == 'Country of Sale') {
			$customer_country_col = $i;
		}
		
		if($trimmedvalue == 'Transaction Date') {
			$transactiondate_col = $i;
		}
		
		if($trimmedvalue == 'Sales Model') {
			$sales_model_col = $i;
		}

		if($trimmedvalue == 'Exchange Rate') {
			$exchange_rate_col = $i;
		}

	}
	

	$brokenline = false;
	$brokenline_nr = false;

	foreach($filecontent as $k => $line) {
			
		if($k == 0) continue;

		$columns = csvLineToArray($line);

		if(strstr($columns[$isbn_col],'E+')) {

			echo usermessage('error', 'Die Datei wurde mit Excel gespeichert und ist unbrauchbar!');
			die();
		}

		$columns[$isbn_col] = str_replace('-', '', $columns[$isbn_col]);
			
		if(!array_key_exists($columns[$product_id_col], $topseller)) {
			$topseller[$columns[$product_id_col]] = 1;					
		} else {
			$topseller[$columns[$product_id_col]] ++;					
		}
		


	}		

	arsort($topseller);
	$topseller = array_slice($topseller, 0, 1000, true);

	foreach($filecontent as $k => $line) {

	
		if(1) {

			$columns = csvLineToArray(utf8_encode($line));
	
			if($brokenline) {
		
				if(count($columns) != $column_count) {
		
					// Wenn Verdacht auf eine korrupte Zeile (z.B: Zeilenumbruch im Kommentarfeld gefunden wird), dass wird brokenline gespeichert und versucht, mit der nächste Zeile zu verbinden
					$line = trim($brokenline)." ".trim($line);
	
					$columns = csvLineToArray(utf8_encode($line));

					//flo('fixing with line '.$k.'');	


					$brokenline = false;
					$brokenline_nr = false;
							
				} else {
					
					if($brokenline_nr == $k-1) {

						//echo'This is line '.$k.', Could not fix line nr. '.$brokenline_nr.', Columns '.count($columns);
	
					}
					
					$brokenline = false;
					$brokenline_nr = false;
					
				}
				
			} else {
				
				if(count($columns) != $column_count) {
	
//					flo('falsche spaltenzahl');
	
					if(substr (trim($columns[$salestype],'"'), 0, 11) == 'Storno ohne') {

						$brokenline = false;						
//						continue 1;
						
					} else {
						$brokenline = $line;
						$brokenline_nr = $k;
		
						echo usermessage('error', 'Die Zeile '.$k.' entspricht nicht dem korrekten Format (Falsche Spaltenzahl)! Starte Reparaturversuch');
						
						continue 1;
						
					}
				}
			}
			
			if($brokenline) die('da stimmt was nicht');
			
			
			$columns[$isbn_col] = str_replace('-', '', $columns[$isbn_col]);

			if(count($columns) < 2) {
				if($k == 0) {
					errorlog($line);
					usermessage('error', 'CSV Datei entspricht nicht dem vereinbarten Format');
					errorlog('CSV Datei entspricht nicht dem vereinbarten Format');
					return;
				}
			}
			
			

			$new_columns = array();

			// Remove dashes from ISBN
		
			if($k != 0) {
			
				$current_distribution_channel = $accounts[$columns[$distributor_id_col]]['name'];

				$shop_channel = getShopChannel($columns[$transaction_id], $current_distribution_channel);						

				foreach($columns as $foo => $bar) {
					
					if(!in_array($column_label[$foo], $hidecols)) {
						
						if($column_label[$foo] == 'Transaction Date') {
							$new_columns[$column_label[$foo]] = substr($bar, 0, 10);
						} else {
							$new_columns[$column_label[$foo]] = $bar;
						}						
					}
				}
				
				$pricediff = 0;


				$transaction_currency = trim($columns[$gross_list_price_currency_col], '"');

				if($transaction_currency != '') { // 0 EUR Storno Transaktionen haben keine WÃ¤hrung
					if($transaction_currency && !array_key_exists($transaction_currency, $values['exchangerates'])) {
						$values['exchangerates'][$transaction_currency] = array();	
					}

					$values['exchangerates'][$transaction_currency][trim(substr($columns[$transactiondate_col],0,7), '"')] = str_replace('-','', trim($columns[$exchange_rate_col], '"'));

				}
			 
				// Zeitzonen aller Transaktionen 
				$transactiondate = trim($columns[$transactiondate_col], '"');

				$timezone = substr($transactiondate, -6);

				if(!array_key_exists($timezone, $values['timezones']['Einzelkauf'])) {
					$values['timezones']['Einzelkauf'][$timezone] = 0;
				}

				//if(trim($columns[$salestype],'"') == 'Einzelkauf') {
					$values['timezones']['Einzelkauf'][$timezone]++;		
				//}


				//if(trim($columns[$salestype],'"') == 'Einzelkauf') {

					if($values['dates']['first_transaction'] === false OR strtotime($transactiondate) < $values['dates']['first_transaction']) {
						$values['dates']['first_transaction'] = strtotime($transactiondate);
					}
	
					if($values['dates']['last_transaction'] === false OR strtotime($transactiondate) > $values['dates']['last_transaction']) {
						$values['dates']['last_transaction'] = strtotime($transactiondate);
					}
				//}



//				if(trim($columns[$salestype],'"') == 'Einzelkauf') {
				if(
					trim($columns[$salestype],'"') == 'Einzelkauf' 
					//OR trim($columns[$salestype],'"') == 'Storno' 
					//OR trim($columns[$salestype],'"') == 'Storno mit Rückr.'
					OR substr (trim($columns[$salestype],'"'), 0, 10) == 'Storno mit' 
					//OR trim($columns[$salestype],'"') == 'Storno ohne Rückr.'				
					OR substr (trim($columns[$salestype],'"'), 0, 11) == 'Storno ohne' 
				) {

						if(!array_key_exists(substr($columns[$transactiondate_col], 0, 10), $values['daily_amounts']['net_unit_price'])) {

							$values['daily_amounts']['net_unit_price'][substr($columns[$transactiondate_col], 0, 10)] = array();
							$values['daily_amounts']['billing_amount'][substr($columns[$transactiondate_col], 0, 10)] = array();
							$values['daily_amounts']['actual_gross_price'][substr($columns[$transactiondate_col], 0, 10)] = array();
							//$values['daily_amounts']['actual_gross_price'][substr($columns[$transactiondate_col], 0, 10)] = array();

							
						}

						if(!array_key_exists($current_distribution_channel, $values['daily_amounts']['net_unit_price'][substr($columns[$transactiondate_col], 0, 10)])) {
//						if(!array_key_exists($columns[$distributor_col], $values['daily_amounts']['net_unit_price'][substr($columns[$transactiondate_col], 0, 10)])) {
													
							$values['daily_amounts']['net_unit_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel] = array();
							$values['daily_amounts']['billing_amount'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel] = array();
							$values['daily_amounts']['actual_gross_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel] = array();
							//$values['daily_amounts']['actual_gross_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel] = array();
							
						}

						
						if(!array_key_exists($columns[$supplier_id_col], $values['daily_amounts']['net_unit_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel])) {
						
							$values['daily_amounts']['net_unit_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$columns[$supplier_id_col]] = 0;
							$values['daily_amounts']['billing_amount'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$columns[$supplier_id_col]] = 0;
							
							$values['daily_amounts']['actual_gross_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$columns[$supplier_id_col]]['EUR'][$columns[$country_of_distributor_col]] = 0;
							$values['daily_amounts']['actual_gross_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$columns[$supplier_id_col]]['CHF'][$columns[$country_of_distributor_col]] = 0;
							
						}


												
						
						$values['daily_amounts']['net_unit_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$columns[$supplier_id_col]] += (str_replace(',','.',(trim($columns[$net_unit_price_col], '"'))));
						$values['daily_amounts']['net_unit_price_by_shopchannel'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$shop_channel] += (str_replace(',','.',(trim($columns[$net_unit_price_col], '"'))));		

										
						$values['daily_amounts']['billing_amount'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$columns[$supplier_id_col]] += (str_replace(',','.',(trim($columns[$billing_amount_col], '"'))));

						// If curreny of actual Price is not set by retailer, we try to guess it by country of Distributor
						//if($columns[$actual_gross_price_currency_col] == '' && $columns[$gross_list_price_col] != '0,00' && $columns[$actual_gross_price_col] != '0,00') {
						if($columns[$actual_gross_price_currency_col] == '') {
							if($columns[$country_of_distributor_col] == 'CH') {

								$columns[$actual_gross_price_currency_col] = 'CHF';

							} else {

								$columns[$actual_gross_price_currency_col] = 'EUR';

							} 
	
						}

						$values['daily_amounts']['actual_gross_price'][substr($columns[$transactiondate_col], 0, 10)][$current_distribution_channel][$columns[$supplier_id_col]][$columns[$actual_gross_price_currency_col]][$columns[$country_of_distributor_col]] += (str_replace(',','.',(trim($columns[$actual_gross_price_col], '"'))));


					$values['net_unit_price']['EUR'] = $values['net_unit_price']['EUR'] + (str_replace(',','.',(trim($columns[$net_unit_price_col], '"'))));

					$values['shopchannel_net_unit_price'][$shop_channel] += str_replace(',','.',(trim($columns[$net_unit_price_col], '"')));


					if(trim($columns[$salestype],'"') == 'Einzelkauf') {
					
						// Fall: Generell die Differenz zwischen Listen-VK und tasächlich erzieltem VK ermitteln 					
						// Wenn die Währungen identisch sind, darf direkt subtrahiert werden
						if($columns[$actual_gross_price_currency_col] == $columns[$gross_list_price_currency_col]) {
							
							$pricediff = (str_replace(',','.',(trim($columns[$actual_gross_price_col], '"'))))-(str_replace(',','.',(trim($columns[$gross_list_price_col], '"'))));
												
						} else {
	
							if($columns[$actual_gross_price_currency_col] == 'CHF' && $columns[$gross_list_price_currency_col] == 'EUR') {
	
								// Wenn der tatsächliche Preis in CHF, der Listenpreis in EUR angegeben ist, ist kein Wechselkurs in der Transaktion enthalten. 
								// Dann muss der Wechselkurs aus dem System (EZB) verwendet werden
	
								$exchange_rate = getExchangeRate(substr($columns[$transactiondate_col], 0, 7), $columns[$actual_gross_price_currency_col]);
	
								$pricediff = (str_replace(',','.',(trim($columns[$actual_gross_price_col], '"')))/$exchange_rate	)-(str_replace(',','.',(trim($columns[$gross_list_price_col], '"'))));
								unset($exchange_rate);
	
							} else if($columns[$actual_gross_price_currency_col] == 'EUR' && $columns[$gross_list_price_currency_col] == 'CHF') {
	
								//flo($columns);
	
								$exchange_rate = getExchangeRate(substr($columns[$transactiondate_col], 0, 7), $columns[$gross_list_price_currency_col]);
	
								$pricediff = (str_replace(',','.',(trim($columns[$actual_gross_price_col], '"'))))-(str_replace(',','.',(trim($columns[$gross_list_price_col], '"')))/$exchange_rate);
	
								$pricediff = round($pricediff/str_replace(',','.',(trim($columns[$exchange_rate_col],'"'))), 2);

							} else {
	
								$pricediff = (str_replace(',','.',(trim($columns[$actual_gross_price_col], '"'))))-(str_replace(',','.',(trim($columns[$gross_list_price_col], '"'))));
							}

						// Transaktionen mit kleineren Preisabweichungen nicht ausgeben!
						}
						
						$pricediff = round($pricediff,2);
						
						if(abs($pricediff) < 0.05) {
							$pricediff = 0;							
						} else {
							
						}
						
	
						// Länder ohne Buchpreisvindung und Reseller-Modell ausnehmen
						if($pricediff == 0 OR (trim($current_distribution_channel,'"') == 'weltbild.ch' AND (trim($columns[$sales_model_col]) == 'Reseller'))) {
							
						} else {
							$new_columns['pricediff'] = $pricediff;
	
							if($pricediff > 0) {
								$values['transactions_with_pricediff_high'][] = $new_columns;	
							} else {
								$values['transactions_with_pricediff_low'][] = $new_columns;	
							}
						}

						// Fall: Verkauf zu 0 Euro mit Listenpreis > 0
						// Nur berechnnen wenn der tatsächliche Verkaufspreis 0 ist ind der Listenpreis > 0  ist
						if(str_replace(',','.',(trim($columns[$actual_gross_price_col],'"'))) == 0 && str_replace(',','.',(trim($columns[$gross_list_price_col],'"'))) != 0) {
	
							$values['transaction_count_free_with_charge'][trim($current_distribution_channel,'"')][] = $new_columns;	
						}

					}

					$values['kpi']['transaction_count']['Einzelkauf'] ++;

					// Nur bei "Storno mit Rückr." den billing_amount saldieren
					if(
						//OR trim($columns[$salestype],'"') == 'Storno mit Rückr.'
						substr (trim($columns[$salestype],'"'), 0, 10) == 'Storno mit' 
						//OR trim($columns[$salestype],'"') == 'Storno ohne Rückr.'				
						//OR substr (trim($columns[$salestype],'"'), 0, 11) == 'Storno ohne' 
					) {

						$values['storno_billing_amount']['EUR'] = $values['storno_billing_amount']['EUR'] + $billingfactor*str_replace(',','.',(trim($columns[$billing_amount_col],'"')));	
						
					}
					
					if(trim($columns[$salestype],'"') == 'Einzelkauf') {

						$values['einzelkauf_billing_amount']['EUR'] = $values['einzelkauf_billing_amount']['EUR'] + $billingfactor*str_replace(',','.',(trim($columns[$billing_amount_col],'"')));	
					
					}

					$values['billing_amount']['EUR'] = $values['billing_amount']['EUR'] + $billingfactor*str_replace(',','.',(trim($columns[$billing_amount_col],'"')));	

					if(array_key_exists(trim($current_distribution_channel,'"'), $values['distributor_billing_amount'])) {
						$values['distributor_billing_amount'][trim($current_distribution_channel,'"')] = $values['distributor_billing_amount'][trim($current_distribution_channel,'"')] + $billingfactor*str_replace(',','.',(trim($columns[$billing_amount_col],'"')));
					} else {
						$values['distributor_billing_amount']['unbekannt'] = $values['distributor_billing_amount']['unbekannt'] + $billingfactor*str_replace(',','.',(trim($columns[$billing_amount_col],'"')));
//						echo usermessage('error', 'Domain nicht gesetzt!');
					}

					if(array_key_exists(trim($current_distribution_channel,'"'), $values['distributor_net_unit_price'])) {
						$values['distributor_net_unit_price'][trim($current_distribution_channel,'"')] = $values['distributor_net_unit_price'][trim($current_distribution_channel,'"')] + $billingfactor*str_replace(',','.',(trim($columns[$net_unit_price_col],'"')));
					} else {
						$values['distributor_net_unit_price']['unbekannt'] = $values['distributor_net_unit_price']['unbekannt'] + $billingfactor*str_replace(',','.',(trim($columns[$net_unit_price_col],'"')));
//						echo usermessage('error', 'Domain nicht gesetzt!');
					}

					// Abrechnungsbetrag je Land des Endkunden speichern
					if(!array_key_exists(trim($columns[$customer_country_col],'"'), $values['customer_country_billing_amount'])) {						
						$values['customer_country_billing_amount'][trim($columns[$customer_country_col],'"')] = '';
					} else {
					}

					$values['customer_country_billing_amount'][trim($columns[$customer_country_col],'"')] += $billingfactor*str_replace(',','.',(trim($columns[$billing_amount_col],'"')));	

					$salesmodel = trim($columns[$sales_model_col], '"');
					$values['salesmodel_billing_amount'][$salesmodel] = $values['salesmodel_billing_amount'][$salesmodel] + $billingfactor*str_replace(',','.',(trim(trim($columns[$billing_amount_col]),'"')));	

					// Abrechnungsbetrag je Land des Retailers speichern
					$currentcountry = trim($columns[$country_of_distributor_col], '"');
					$values['country_billing_amount'][$currentcountry] = $values['country_billing_amount'][$currentcountry] + $billingfactor*str_replace(',','.',(trim(trim($columns[$billing_amount_col]),'"')));	
					
					
					// Brutto VK in EUR nach Umrechnung von CHF in EUR
					if($columns[$gross_list_price_col] != 0) {
						$values['gross_list_price_sum']['EUR'] = $values['gross_list_price_sum']['EUR'] + (str_replace(',','.',(trim($columns[$gross_list_price_col],'"')))/str_replace(',','.',(trim($columns[$exchange_rate_col],'"'))));
					}


					// Brutto Listen-VK je WÃƒÂ¤hrung (ohne Umrechnung)
					if(trim($columns[$gross_list_price_currency_col],'"') != '') {
						$values['gross_list_price'][trim($columns[$gross_list_price_currency_col],'"')] += (str_replace(',','.',(trim($columns[$gross_list_price_col],'"'))));		
					}



					// Brutto tatsächlich erzielter VK je Währung (ohne Umrechnung)
					//$values['actual_gross_price'][trim($columns[$actual_gross_price_currency_col],'"')] = $values['actual_gross_price'][trim($columns[$actual_gross_price_currency_col],'"')] + (str_replace(',','.',(trim($columns[$actual_gross_price_col],'"'))));	
					$values['actual_gross_price'][trim($columns[$actual_gross_price_currency_col],'"')] += (str_replace(',','.',(trim($columns[$actual_gross_price_col],'"'))));	


					if($partnerconfig['partnertype'] == 'Debitor') {
						
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['title'] = $columns[$title_col];
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['cdp-id'] = $columns[$product_id_col];
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['author'] = $columns[$author_col];
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['supplier'] = $columns[$supplier_col];
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['publisher'] = $columns[$publisher_col];
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['distributor'] = $partnerconfig['id'];
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['job'] = $job;
						$values['isbn_index'][str_replace('-','',$columns[$isbn_col]).'_'.$columns[$supplier_id_col].'_'.$columns[$product_id_col].'_'.$columns[$distributor_id_col].'_'.$faktura_id]['salescount'] ++;
					}

					if(array_key_exists($columns[$product_id_col], $topseller)) {		
					
						//flo(utf8_encode($columns[$publisher_col]));
								
						if(!array_key_exists($columns[$publisher_col].' - '.$columns[$supplier_col], $values['pivots']['publisher'])) {
							$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]] = array();
							$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]]['billing_amount'] = 0;	
							$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]]['publisher'] = '';	
							$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]]['count'] = 0;	
							//flo($columns[$supplier_col]);
						}				
						
	
						$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]]['billing_amount'] += $billingfactor*str_replace(',','.',(trim(($columns[$billing_amount_col]),'"')));	
						$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]]['publisher'] = $columns[$publisher_col];	
						$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]]['supplier'] = (trim($columns[$supplier_col],'"'));	
						$values['pivots']['publisher'][$columns[$publisher_col].' - '.$columns[$supplier_col]]['count']++;	
	
	
//						if($partnerconfig['partnertype'] == 'Kreditor' OR ($partnerconfig['partnertype'] == 'Debitor' AND $partnerconfig['tier'] == 1)) {


							if(!array_key_exists($columns[$product_id_col], $values['pivots']['products'])) {
							
								$values['pivots']['products'][$columns[$product_id_col]] = array();
								$values['pivots']['products'][$columns[$product_id_col]]['count'] = 0;	
								$values['pivots']['products'][$columns[$product_id_col]]['net_unit_price_sum'] = 0;	
								$values['pivots']['products'][$columns[$product_id_col]]['title'] = '';
								$values['pivots']['products'][$columns[$product_id_col]]['isbn'] = '';
								$values['pivots']['products'][$columns[$product_id_col]]['author'] = '';
								$values['pivots']['products'][$columns[$product_id_col]]['salesmodel'] = '';
								$values['pivots']['products'][$columns[$product_id_col]]['publisher'] = '';
								$values['pivots']['products'][$columns[$product_id_col]]['supplier_id'] = '';
			
		
								$values['pivots']['products'][$columns[$product_id_col]]['distribution_channels'] = array();
								foreach($accounts as $account_id => $account) {
									if($account['tier'] == '0') {
										$values['pivots']['products'][$columns[$product_id_col]]['distribution_channels'][$account_id] = array();
										$values['pivots']['products'][$columns[$product_id_col]]['distribution_channels'][$account_id]['net_unit_price'] = 0;
										$values['pivots']['products'][$columns[$product_id_col]]['distribution_channels'][$account_id]['count'] = 0;
									}
								}
		
		
								$values['pivots']['products'][$columns[$product_id_col]]['prices'] = array();
								$values['pivots']['products'][$columns[$product_id_col]]['prices']['EUR'] = array();
								$values['pivots']['products'][$columns[$product_id_col]]['prices']['EUR']['0,00'] = 0;
								$values['pivots']['products'][$columns[$product_id_col]]['prices']['CHF'] = array();
								$values['pivots']['products'][$columns[$product_id_col]]['prices']['CHF']['0,00'] = 0;
		//						$values['pivots']['products'][$columns[$product_id_col]]['prices'][''] = array();
		//						$values['pivots']['products'][$columns[$product_id_col]]['prices']['']['0,00'] = 0;
	
							}				

							$values['pivots']['products'][$columns[$product_id_col]]['net_unit_price_sum'] += str_replace(',','.',(trim(($columns[$net_unit_price_col]),'"')));	
							$values['pivots']['products'][$columns[$product_id_col]]['title'] = $columns[$title_col];
							$values['pivots']['products'][$columns[$product_id_col]]['isbn'] = $columns[$isbn_col];
							$values['pivots']['products'][$columns[$product_id_col]]['author'] = $columns[$author_col];
							$values['pivots']['products'][$columns[$product_id_col]]['salesmodel'] = $columns[$sales_model_col];
							$values['pivots']['products'][$columns[$product_id_col]]['publisher'] = $columns[$publisher_col];
							$values['pivots']['products'][$columns[$product_id_col]]['supplier_id'] = $columns[$supplier_id_col];					
							$values['pivots']['products'][$columns[$product_id_col]]['count']++;	
	
							// If curreny of actual Price is not set by retailer, we try to guess it by country of Distributor
							//if($columns[$actual_gross_price_currency_col] == '' && $columns[$gross_list_price_col] != '0,00' && $columns[$actual_gross_price_col] != '0,00') {
							if($columns[$gross_list_price_currency_col] == '') {
								if($columns[$country_of_distributor_col] == 'CH') {
	
									$columns[$gross_list_price_currency_col] = 'CHF';
	
								} else {
	
									$columns[$gross_list_price_currency_col] = 'EUR';
	
								} 
		
							}
							
							if(!array_key_exists($columns[$gross_list_price_col], $values['pivots']['products'][$columns[$product_id_col]]['prices'][$columns[$gross_list_price_currency_col]])) {
	
									$values['pivots']['products'][$columns[$product_id_col]]['prices'][$columns[$gross_list_price_currency_col]][$columns[$gross_list_price_col]] = 0;
									$values['pivots']['products'][$columns[$product_id_col]]['prices'][$columns[$gross_list_price_currency_col]][$columns[$gross_list_price_col]] ++;
								
							}		
		
							$values['pivots']['products'][$columns[$product_id_col]]['distribution_channels'][$columns[$distributor_id_col]]['net_unit_price'] += str_replace(',','.',(trim(($columns[$net_unit_price_col]),'"')));	
							$values['pivots']['products'][$columns[$product_id_col]]['distribution_channels'][$columns[$distributor_id_col]]['count'] ++;	
						}
					
				}
				
				// Hier zusätzlich noch die reinen Stornos behandeln
				if(
					trim($columns[$salestype],'"') == 'Storno' 
					//OR trim($columns[$salestype],'"') == 'Storno mit Rückr.'
					OR substr (trim($columns[$salestype],'"'), 0, 10) == 'Storno mit' 
					//OR trim($columns[$salestype],'"') == 'Storno ohne Rückr.'				
					OR substr (trim($columns[$salestype],'"'), 0, 11) == 'Storno ohne' 
				) {
				
					//$values['transaction_count']['Storno'] ++;		
					$values['kpi']['transaction_count']['Storno']++;
					
					$values['storno_transactions'][] = $new_columns;
					//flo($new_columns);
					//die();
								
				}
				
			}
		
		}
			
	}

	arsort($topseller);

	$values['kpi']['kondition'] = round($billingfactor*100*$values['billing_amount']['EUR']/$values['net_unit_price']['EUR'], 1);
	$values['kpi']['avg_net_price'] = round($values['net_unit_price']['EUR']/$values['kpi']['transaction_count']['Einzelkauf'],2);
	$values['kpi']['transaction_count']['Einzelkauf'] = $values['kpi']['transaction_count']['Einzelkauf'];
	
	return $values;
}

function getShopChannel($tid, $distributor) {

	switch($distributor) {

		case 'buecher.de':

			$parts = explode('_', $tid);

			if(count($parts) == 1) {

				$channel = 'unbekannt';

			} else {
				switch($parts[0]) {

					case 'BWS':						
						$channel = 'Webshop';
					break;	
					case 'MOB':						
						$channel = 'mobile';
					break;	
					case 'TOS':						
						$channel = 'eink';
					break;	
	
					case 'TOT':						
						$channel = 'tablet';
					break;	
	
					case 'WOL':			
					case 'PLU':						
					case 'NET':			
						$channel = 'whitelabel';
					break;	
	
					default:			
						$channel = 'unbekannt';
					break;	
				}
			}
		break;
		default:

			$parts = explode('_', $tid);

			if(count($parts) == 1) {

				$channel = 'unbekannt';

			} else {
				switch($parts[0]) {

					case 'WE':						
						$channel = 'Webshop';
					break;	
					case 'MO':						
						$channel = 'mobile';
					break;	
					case 'EI':						
						$channel = 'eink';
					break;	
	
					case 'TA':						
						$channel = 'tablet';
					break;	
	
					case 'WL':			
						$channel = 'whitelabel';
					break;	
	
					default:			
						$channel = 'unbekannt';
					break;	
				}
			}
		break;
	}

	//die($channel );

	return $channel;
}



function getAmounts($job) {
	
	die('getAmounts() wurde in functions.php deaktiviert');

		global $DIR_070_SENT_FILES;

		$dir = $DIR_070_SENT_FILES.$job.'/';

		$files = loadDir($dir, false, 'sent');

		$fileinfo = '';

		foreach($files as $foo => $bar) {	
			if($bar != 'CSV') {				
				$fileinfo .= $bar.' ('.size_readable(filesize($dir.$bar)).')'."\n";

				$parts = explode(".", $bar);

				if(array_key_exists(2, $parts)) {
					if($parts[2] == 'zip') {
						$amounts = readEVN($dir.'/CSV/'.$parts[0].'.'.$parts[1]);
					}
				}
				
			}
			
		}

	return $amounts['billing_amount']['EUR'];
	
}


function hasBeenCancelled($job) {

	$parts = explode('_', $job);
 
	if(array_key_exists(3, $parts) && $parts[3] == 'STORNO') {
		//return true;

		return true;
	} else {
		return false;
	}
}

function getBillingAmounts($job, $mode = 'view') {

	$storno_result = hasBeenCancelled($job);

	if($storno_result) {
		return -1;
//		return false;
	}

		
	global $DIR_070_SENT_FILES, $DIR_060_IMPORTED_FILES;
	
	if($mode == 'proforma' OR $mode == 'preview') {
		$file = $DIR_060_IMPORTED_FILES.$job.'/billingdata.cache';
	} else {
		$file = $DIR_070_SENT_FILES.$job.'/billingdata.cache';
	}

	if(file_exists($file)) {
		$amounts = unserialize(file_get_contents($file));
	} else {
		$amounts = false;			
	}

	return $amounts;
	
}

function getStatistics($job, $mode = 'view') {

	global $DIR_070_SENT_FILES, $DIR_060_IMPORTED_FILES;
	
	if($mode == 'proforma' OR $mode == 'preview') {
		$file = $DIR_060_IMPORTED_FILES.$job.'/statistics.cache';
	} else {
		$file = $DIR_070_SENT_FILES.$job.'/statistics.cache';
	}

	if(file_exists($file)) {
		$amounts = unserialize(file_get_contents($file));
	} else {
		$amounts = false;			
	}

	if(!$amounts OR !$amounts['products']) {
		viewFaktura($job, 'view', 'sent');
		$amounts = unserialize(file_get_contents($file));
	}
		
	return $amounts;
	
}

function getPeriodStatistics($period, $group_by = false) {

	global $DIR_070_SENT_FILES, $DIR_060_IMPORTED_FILES, $accounts;
	
	
	// LIst dirs
	// Select Dirs
	// Add Stats to array

	$dirs = loadDir(false, false, 'sent', $period);

	$amounts = array();

	foreach($dirs as $i => $dir) {
		$parts = explode('_',$dir);
	
		if($accounts[$parts[1]]['tier'] != 0) {
			
			$myarr = getBillingAmounts($dir);	 

			if($group_by == 'group') {
			
				$group = $accounts[$parts[1]]['group'];

				if(array_key_exists($group, $amounts)) {
				
					$amounts[$group]['net_unit_price_sum']['EUR'] += $myarr['net_unit_price']['EUR']; 		
					$amounts[$group]['label'] = $accounts[$parts[1]]['group']; 		
					//$amounts[$group]['group'] = $accounts[$parts[1]]['group']; 		
					
				} else {
					$amounts[$group]['net_unit_price_sum'] = $myarr['net_unit_price']; 		
					$amounts[$group]['label'] = $accounts[$parts[1]]['name']; 		
					$amounts[$group]['group'] = $accounts[$parts[1]]['group']; 		
				
				}
				
				$sum = 0;
				
				foreach($myarr['distributor_net_unit_price'] as $domain => $value) {						
					$sum += abs($value);
					
					if(array_key_exists('distribution_channels', $amounts[$group]) && array_key_exists($domain, $amounts[$group]['distribution_channels'])) {
						$amounts[$group]['distribution_channels'][$domain] += abs($value); 					
						
					} else {
						$amounts[$group]['distribution_channels'][$domain] = abs($value); 					
						
					}
				}
							
				
			} else {
				$amounts[$parts[2]]['net_unit_price_sum'] = $myarr['net_unit_price']; 		
				$amounts[$parts[2]]['label'] = $accounts[$parts[1]]['name']; 		
				$amounts[$parts[2]]['group'] = $accounts[$parts[1]]['group']; 		

				$sum = 0;
				
				foreach($myarr['distributor_net_unit_price'] as $domain => $value) {
						
					$sum += abs($value);

					$amounts[$parts[2]]['distribution_channels'][$domain] = abs($value); 					
	
				}	
			}			
		}

	}


	return $amounts;
	
}



function curPageURL() {
	$pageURL = 'http';
	if (array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}

function validate_password($passwort) {

	if(strlen($passwort) >= 6) {
		return true;	
	} else {
		return false;
	}
	
}

function validate_username($username) {

	if(strlen($username) >= 6) {
		return true;	
	} else {
		return false;
	}
}


function getExchangeRate($period, $currency) {

	if(strlen($period) == 10) {
		$period = substr($period, 0,7);
	}

	if($currency == 'EUR') {
		return '1.0000';
	}

	global $CURRENCY_FILES;

	$dir = $CURRENCY_FILES[$currency]['cache_dir'];

	if ($handle = opendir($dir)) {
		$targetfile = $dir.$currency ;
		if(file_exists($targetfile)) {
	
			$cached = unserialize(file_get_contents($targetfile));
			
			if(!array_key_exists($period, $cached['values'])) {

			} else {
			
				return str_replace(',', '.', $cached['values'][$period]);

			}
		} else {

		}
	}
	return false;

}

function loadratesFromEZB($url, $currency) {

	if (($handle = fopen($url, "r")) !== FALSE) {

		$row = 0;
		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
	
			$skiprow = false;
	
			$num = count($data);
	
			//flo($data);
	
			if($row == 1) {
				$rates['info']['text'] = $data[1];
			} else if($row == 2) {
				$rates['info']['currency'] = $data[1];
			} else {
				for ($c=0; $c < count($data); $c++) {
				
					if($c == 0 && (!is_numeric(substr($data[$c], 5,2)) OR !is_numeric(substr($data[$c], 0,4)))) {
						$skiprow = true;
						$c = $num;
					} else {
		
						$rates['values'][$data[0]] = $data[1];
					}
				}
			}
		        $row++;
		}

		fclose($handle);
	
		$string = serialize($rates);
		
		$targetfile = '.data/exchangerates/'.$rates['info']['currency'];
		
		
		if(file_put_contents($targetfile, $string)) {
		
			echo usermessage('success', 'Die aktuellen Wechselkurse ('.$currency.') von '.$url.' wurden geladen und gespeichert.');	
		} else {
	
			echo usermessage('error', 'Fehler beim Abruf der Wechselkurse ('.$currency.') von '.$url.'.');	
		}

	} else {
		echo usermessage('error', 'Fehler beim Abruf der Datei '.$url.'.');	
	}

	return;
}


function setURLParam($setparam) {

	$current_url = curPageURL();

	$parts = explode('?', $current_url);

	$filepath = $parts[0];

	if(count($parts) == 1) { // Keine Query hinter URL
		return $parts[0].'?'.$setparam[0].'='.$setparam[1];
	}
	$query = $parts[1];


	$queryparts = explode('&', $query);
	$newqueryparts = array();

	$found = false;

	foreach($queryparts as $i => $pair) {

		$param = explode('=', $pair);

		if($param[0] == $setparam[0]) {
			$newqueryparts[] = $param[0].'='.$setparam[1];
			$found = true;
		} else {
			$newqueryparts[] = $param[0].'='.$param[1];
		}
	}
	
	if($found == false) {
		$newqueryparts[] = $setparam[0].'='.$setparam[1];
	}

	$url = $filepath.'?'.implode('&', $newqueryparts);
	return $url;
}

function convertAmount($amount, $currency, $date) {

	if($amount == 0 OR $currency == 'EUR') {
		return $amount;
	}
	
	$amount = str_replace(',', '.', $amount);
	
	$rate = getExchangeRate($date, $currency);

	$eur_amount = $amount/$rate;
			
	return $eur_amount;

}

function calculateNetPrice($amount, $country, $date) {

	$amount = str_replace(',', '.', $amount);

	if($amount == 0) {
		return $amount;
	}
	
	if($country == 'DE') {
		
		$net_amount = $amount/(1.19);
		
	} else if ($country == 'AT') {

		$net_amount = $amount/(1.20);

	} else if ($country == 'CH') {
		
		$net_amount = $amount/(1.08);

	} else {
	
		die('Unbekannte WÃ¤hrung: '.$currency.', Betrag '.$amount.'');
	
	}
			
	return $net_amount;

}


/**
 * Unzip the source_file in the destination dir
 *
 * @param   string      The path to the ZIP-file.
 * @param   string      The path where the zipfile should be unpacked, if false the directory of the zip-file is used
 * @param   boolean     Indicates if the files will be unpacked in a directory with the name of the zip-file (true) or not (false) (only if the destination directory is set to false!)
 * @param   boolean     Overwrite existing files (true) or not (false)
 * 
 * @return  boolean     Succesful or not
 */
function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true)
{

  if ($zip = zip_open($src_file))
  {
    if ($zip)
    {
      $splitter = ($create_zip_name_dir === true) ? "." : "/";
      if ($dest_dir === false) $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";
     
      // Create the directories to the destination dir if they don't already exist
      create_dirs($dest_dir);

      // For every file in the zip-packet

      $count = 0;

      while ($zip_entry = zip_read($zip))
      {

        // Now we're going to create the directories in the destination directories
       
        // If the file is not in the root dir
        $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
        if ($pos_last_slash !== false)
        {
          // Create the directory where the zip-entry should be saved (with a "/" at the end)
          create_dirs($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
        }

        // Open the entry
        if (zip_entry_open($zip,$zip_entry,"r"))
        {
         
          // The name of the file to save on the disk
          $file_name = $dest_dir.zip_entry_name($zip_entry);
         
          // Check if the files should be overwritten or not
          if ($overwrite === true || $overwrite === false && !is_file($file_name))
          {
            // Get the content of the zip entry
            $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            file_put_contents($file_name, $fstream );
            // Set the rights
            chmod($file_name, 0777);

		//echo usermessage('info', 'unzipping: '.$file_name.'');

              //echo "save: ".$file_name."<br />";
          }
         

          // Close the entry
          zip_entry_close($zip_entry);
          $count++;
        }      
      }
      // Close the zip-file
      zip_close($zip);

    }
  }
  else
  {
    return false;
  }
	if($count > 0)  {
		return true;
	} else {
		echo usermessage('error', 'Die Zip Datei konnte nicht gelesen werden (0 Eintr&auml;ge)');
		return false;
	}

}

/**
 * This function creates recursive directories if it doesn't already exist
 *
 * @param String  The path that should be created
 * 
 * @return  void
 */
function create_dirs($path)
{
  if (!is_dir($path))
  {
    $directory_path = "";
    $directories = explode("/",$path);
    array_pop($directories);
   
    foreach($directories as $directory)
    {
      $directory_path .= $directory."/";
      if (!is_dir($directory_path))
      {
        mkdir($directory_path);
        chmod($directory_path, 0777);
      }
    }
  }
}


function cacheBillingData($amounts, $dir, $path) {

	global $DIR_070_SENT_FILES, $DIR_060_IMPORTED_FILES;

	$pivots = $amounts['pivots'];
	$isbn_index = $amounts['isbn_index'];

	unset($amounts['pivots']);
	unset($amounts['isbn_index']);

	//flo($pivots);
	
	if($path == 'sent') {
		$targetdir = $DIR_070_SENT_FILES.$dir.'/';
	} else {
		$targetdir = $DIR_060_IMPORTED_FILES.$dir.'/';
	}

	$ISBN_INDEX_DIR = '../100_ISBN_INDEX/';
	
	$targetfile = $targetdir.'billingdata.cache';

	$res1 = file_put_contents($targetfile, serialize($amounts));

	$pivots_file = $targetdir.'statistics.cache';

	$res2 = file_put_contents($pivots_file, serialize($pivots));

	if(count($isbn_index) > 0) {

		$isbn_index_file = $ISBN_INDEX_DIR.'isbn_index.'.$dir.'.cache';
		$res3 = file_put_contents($isbn_index_file, serialize($isbn_index));
	} else {
		$res3 = true;
	}

	if($res1 && $res2 && $res3) {
		return true;
	} else {
		return false;
	}
	
}


function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}



function innerHTML($el) {
  $doc = new DOMDocument();
  $doc->appendChild($doc->importNode($el, TRUE));
  $html = trim($doc->saveHTML());
  $tag = $el->nodeName;
  return preg_replace('@^<' . $tag . '[^>]*>|</' . $tag . '>$@', '', $html);
}


function isbnInputForm($domains, $selected_domains = false, $isbnlist = false) {

	$html .= '<table id="edit_users" border="1"><tr><td>';

	if($isbnlist) {
		$isbnlist = implode("\n",$isbnlist);

	}
	
	$html .= '<label for="file">ISBN-Liste</label><textarea name="isbnlist" style="height: 150px;">'.$isbnlist.'</textarea><br><br>';


	$html .= '<label for="">Domains</label><div class="dropdown dropdown-dark" style="height: auto; width: auto;"><select name="domains[]" size="15" multiple class="dropdown-select" style="width: 200px; height: 200px;">';		


	foreach($domains as $country => $countrydomains) {
		// Creating Target-Cells
		foreach($countrydomains as $domain) {

			$id = str_replace('.','_', $domain).'_'.$isbn;
			$id = str_replace('-','_', $id);

			if(isset($selected_domains[$country]) && in_array($domain, $selected_domains[$country])) {

				$html .= '<option value="'.$country.' '.$domain.'" SELECTED>'.$domain.'</option>';	
			} else {
				$html .= '<option value="'.$country.' '.$domain.'">'.$domain.'</option>';	
			}


			/*
			$html .= '<div><label for="">'.$domain.'</label>';		
			$html .= '<input type="checkbox" style="width: 50px; margin-left: 10px; visibility: visible;" checked name="domains['.$country.'][]" value="'.$domain.'"></div><br>';
			*/
		}
	}

	$html .= '</select></div>';

//	$html .= '<br><br><label for="file">Methode</label><div class="dropdown dropdown-dark"><select name="method" class="dropdown-select"><option value="default">Default</option><option value="keywords">Keywords</option></select></div><br>';

	$html .= '</td><tr></table>';

	return dialog('modal', 'Realtime-Preisvergleich - ISBN eingeben', 'Bitte eine Liste von ISBN-Nummern (zeilenweise, Komma-getrennt, Tab-getrennt) eingeben:<br>'.$html.'', 'pricegrabber.php?confirm=true', 'pricegrabber.php');	
}


function load_isbn_index($query, $searchtype) {

	$month_limit = 12;

	$firstday = date('Y-m-d', strtotime('first day of last month -3 month'));



	$result = false;

	$dir = '../100_ISBN_INDEX/';

	if ($handle = opendir($dir)) {

   	 	while (false !== ($file = readdir($handle))) {
   	 	
   	 		if ($file != "." && $file != "..") {

				$fileparts = explode('.', $file);
				$fileparts = explode('_', $fileparts[1]);

				$firstday = date('Y-m-d', strtotime('first day of last month -3 month'));

				if($fileparts[0].'-01' < $firstday) {

					//flo('Skipping! File too old: '.$fileparts[0]);

					continue;

				}


				$index = unserialize(file_get_contents($dir.'/'.$file));

				$my_res = array();
				foreach($index as $id => $info) {

					if($searchtype == 'titlesearch' OR $searchtype == 'titlesearch_2') {

						if(strpos(strtolower($info['title']), $query) !== false OR strpos(strtolower($info['author']), $query) !== false) {

							$my_res[$id] = $info;		
				
						}
						unset($index[$id]);

					} else if($searchtype == 'pidsearch') {



						if($info['cdp-id'] == $query) {

							$my_res[$id] = $info;		
				
						}
						unset($index[$id]);

					} else {
	
						if(strpos($id,$query) !== false) {
							$my_res[$id] = $info;
						} else {
							
						}
						unset($index[$id]);
					}

				}

				if($result === false) {
					$result = $my_res;
				} else {
					$result = $result + $my_res;
				}

				unset($index);
        	}
   	 	}
	} else {
		echo usermessage('error', 'Verzeichnis '.$dir.' konnte nicht geöffnet werden!');
	}

	return $result;
}
	
function getCommissionRate($date, $partnerid) {

	global $COMMISSION_CONFIG;

	if(!array_key_exists($partnerid, $COMMISSION_CONFIG)) {

		// Wenn keine Konfiguration auf Partnerebene, dann Fallback auf default
		$valid_rate = getCommissionRate($date, 'default');
		
		if(!$valid_rate) {
			echo usermessage('error', 'Commission für "'.$partnerid.'" und Datum '.$date.' ist nicht definiert!');
			return false;
		} else {
			return $valid_rate;
		}

	} else {

		$valid_rate= false;

		foreach($COMMISSION_CONFIG[$partnerid] as $startdate => $rate) {

			if($startdate <= $date) {
				$valid_rate = $rate;
			}

		}

		if($valid_rate == 'default') {
			$valid_rate = getCommissionRate($date, 'default');
		}
		return $valid_rate;
	}	
}

function getTaxRate($date, $countrycode) {

	global $TAX_CONFIG;

	if(!array_key_exists($countrycode, $TAX_CONFIG)) {

		echo usermessage('error', 'Steuersatz für "'.$countrycode.'" und Datum '.$date.' ist nicht definiert!');

		return false;

	} else {

		$valid_taxrate = false;

		foreach($TAX_CONFIG[$countrycode] as $startdate => $taxrate) {

			if($startdate <= $date) {
				$valid_taxrate = $taxrate;
			}

		}
		return $valid_taxrate;
	}	
}




function makeSystemBackup() {

	$dirs = array();
	$dirs[] = '../wwwroot/';
	
	$files = array();
	
	//$html = '';	
	//$html .= '<h1>Backup erzeugen</h1>';
	
	$zippedfiles = array();
	
	//$html .= '<table id="folders">';
	
	$filename= '../source_backup/backup_'.time().'.zip';
	
	$res = recursiveZip($dirs[0], $filename);
	
	if(file_exists($filename)) {
	
		$za = new ZipArchive(); 
	
		$za->open($filename); 

		for( $i = 0; $i < $za->numFiles; $i++ ){ 
		    $stat = $za->statIndex( $i ); 
		    //print_r( basename( $stat['name'] ) . PHP_EOL ); 
		}
	
	
		//echo usermessage('success', 'Datei '.$filename.' (Dateigr&ouml;&szlig;e: '.size_readable(filesize($filename)).', Anzahl Dateien: '.$za->numFiles.') wurde angelegt');
	
		if(!$res){
			echo usermessage('error', 'Zip nicht erfolgreich:');
		} else {
			echo usermessage('success', 'Zip erfolgreich: '.$filename);
		}
	
	} else {
		echo usermessage('error', 'Datei '.$filename.' wurde nicht angelegt');
	
	}
}






function recursiveZip($source, $destination)
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


function foldersize($path) {

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