<?php
ini_set('display_errors', true);  

require_once('_functions/functions.php');

include_once('inc/header.php');


checkrights('admin', $USERDATA);

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);  

$pid = $_GET["pid"];
//$isbn = $_GET["isbn"];

if($pid == '') {
	echo usermessage('error', 'Keine Produkt-ID angegeben');
	die();
}


$isbnlist = getArticleOverview($pid);

ksort($isbnlist);

$partners = load_partners($PARTNERS_FILE);

//flo($isbnlist);

$html = '';
$html = '<h1>ISBN</h1>';
$html .= '<table class="styled_table">';

$i = 0;
foreach($isbnlist as $isbn) {

	if($i == 0) {
		// Creating Headers
		$html .= '<tr>';

		$html .= '<th colspan="">Periode</th>';
		$html .= '<th colspan="">ISBN</th>';
		$html .= '<th colspan="">Titel</th>';
		$html .= '<th colspan="">Lieferant</th>';
		$html .= '<th colspan="">Verlag</th>';
		$html .= '<th colspan="">Vertriebspartner</th>';
		$html .= '<th colspan="">Job</th>';
		$html .= '<th colspan="">Verk&auml;ufe</th>';

		$html .= '</tr>';

	}

	$html .= '<tr>';

	$html .= '<td>'.$isbn['period'].'</td>';
	$html .= '<td>'.$isbn['isbn'].'</td>';
	$html .= '<td>'.$isbn['title'].'</td>';
	$html .= '<td>'.$isbn['supplier'].'</td>';
	$html .= '<td>'.$isbn['publisher'].'</td>';
	$html .= '<td><a href="'.getArticlepageUrl($isbn['isbn'], $partners[$isbn['distributor']]['name']).'" target="_blank">'.$partners[$isbn['distributor']]['name'].'</a></td>';
	$html .= '<td><a href="view.php?job='.$isbn['job'].'" target="_blank">'.$isbn['job'].'</td>';
	$html .= '<td>'.$isbn['salescount'].'</td>';

	$html .= '</tr>';

	$i ++;

}

$html .= '</table>';
		
		
print($html);


include('inc/footer.php');
die();



function getArticleOverview($query) {
	
	global $USERDATA;
	global $PARTNERS_FILE;

	$searchtoken[] = $query;

	$result = array();

	$isbns = load_isbn_index($query, 'pidsearch');

	foreach($isbns as $identifier => $isbn_infos) {
	
		$parts = explode('_', $identifier);
		$jobparts = explode('_', $isbn_infos['job']);
		
		$isbn = $parts[0];
		$supplier_id = $parts[1];
		$distributor_id = $parts[3];


		$identifier = $jobparts[0].'_'.$jobparts[1].'_'.$supplier_id.'_'.$jobparts[2];


		if($isbn_infos['cdp-id'] == $query) {


			$isbn_infos['isbn'] = $isbn;
			$isbn_infos['period'] = $jobparts[0];

			$result['isbn_'.$identifier] = $isbn_infos;		

		} else {

		}
	}
	
	$result = convertSearchResult($result, 'eansearch');

	return ($result);
}


function convertSearchResult($result, $searchtype) {
	
	switch($searchtype) {
		
		case 'titlesearch':
		case 'titlesearch_2':
		
			
			foreach($result as $key => $value) {

				if(array_key_exists('salescount', $value)) {
					$result[$key]['label'] = $value['label'].' (VerkŠufe: '.$value['salescount'].')';
				}
			
			}
			
		break;
	}

	return $result;
		
}



?>