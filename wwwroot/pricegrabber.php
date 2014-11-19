<?php

require_once('_functions/functions.php');

checkrights('user', $USERDATA);

if(isset($_GET["confirm"])) {
	$confirm = $_GET["confirm"];
} else {
	//die('Job not defined!');
}

if(isset($_POST["method"])) {
	$method= $_POST["method"];
} else {
	$method = 'default';
}

//error_reporting(E_ALL ^ E_WARNING);
//ini_set('display_errors', TRUE);  
//ini_set('error_reporting', E_ALL);

$domains['DE'][] = 'langenscheidt.de';	
$domains['DE'][] = 'buchkatalog.de';	

$domains['DE'][] = 'amazon.de';	
$domains['DE'][] = 'ebook.de';
$domains['DE'][] = 'libreka.de';

$domains['DE'][] = 'weltbild.de';
$domains['DE'][] = 'buecher.de';
$domains['DE'][] = 'hugendubel.de';

$domains['DE'][] = 'derclub.de';
//$domains['DE'][] = 'otto-media.de';
$domains['DE'][] = 'thalia.de';
$domains['DE'][] = 'buch.de';

$domains['AT'][] = 'weltbild.at';		
//$domains['AT'][] = 'donauland.at';
$domains['AT'][] = 'thalia.at';

$domains['CH'][] = 'weltbild.ch';
$domains['CH'][] = 'thalia.ch';
$domains['CH'][] = 'buch.ch';



if($confirm && $method == 'keywords') {

	
	$selected_domains = array();
	
//	$selected_domains['DE'][] = 'langenscheidt.de';
	$selected_domains['DE'][] = 'amazon.de';
	
	
} else if($confirm && isset($_POST["domains"])) {

	$selected_domains = array();
	
	foreach($_POST["domains"] as $post_domain) {
	
		$parts = explode(' ', $post_domain);
		$selected_domains[$parts[0]][] = $parts[1];
		
	}
}




if(!$confirm) {

	include_once('inc/header.php');

	$html .= isbnInputForm($domains);

	print($html);



} else {
	// Define which Domains should be crawled

	$isbnlist = explode(';', $_POST['isbnlist']);
	
	if(count($isbnlist) == 1) {
		$isbnlist = explode('	', $_POST['isbnlist']);
	}
	
	if(count($isbnlist) == 1) {
		$isbnlist = explode(',', $_POST['isbnlist']);
	}
	
	foreach($isbnlist as $key => $isbn) {
		$isbnlist[$key] = trim($isbn);
	}
	
	if(count($isbnlist) == 1) {
		$isbnlist = explode("\n", $_POST['isbnlist']);
	}
	
	if(count($isbnlist) == 1) {
		$isbnlist = explode(" ", $_POST['isbnlist']);
	}
	
	foreach($isbnlist as $key => $value) {
		$value = trim($value);
		
		$value = str_replace('-','', $value);
		
		if($value != '') {
			$isbnlist[$key] = $value;
		} else {
			unset($isbnlist[$key]);
		}
		
	}



	checkrights('user', $USERDATA);
	
			
		$custom_header .= '<link rel="stylesheet" href="css/tooltip.css" type="text/css" media="screen,projection,print">'."\n";

		$custom_header .= <<<EOT


<style type="text/css" title="currentStyle">
	@import "data-tables/media/css/TableTools.css";
</style>

<script type="text/javascript" language="javascript" src="data-tables/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" language="javascript" src="data-tables/media/js/TableTools.min.js"></script>


<script>

$(document).ready(function() {

    $("#tabs-min").tabs();


/*

	$('#dummy_pricecomparison').dataTable( {

		"bAutoWidth": false,
		"sDom": 'T<"clear">lfrtip',
		"bPaginate": false,
		"bLengthChange": false,
		"bFilter": false,
		"bSort": true,
		"bInfo": true,
		"oLanguage": {
			"sSearch": "<div>Filter: </div>"
		}
	} );
*/

} );




</script>



EOT;


/*
		$custom_header .= '<script>'."\n";			
		$custom_header.= '$(function() {'."\n";
		$custom_header.= '$( "#tabs-min" ).tabs();'."\n";
		$custom_header.= '});'."\n";
		$custom_header .= '</script>'."\n";			
*/
		include_once('inc/header.php');

		$html = '';

		$html = '<h1>Realtime-Preisvergleich: Ergebnisse</h1>';
	


		$table_dummy = '<table id="dummy_pricecomparison">';
		$table = '<table id="pricecomparison">';

		$i = 0;
		foreach($isbnlist as $isbn) {
		
			if($i == 0) {
				// Creating Headers
				$table .= '<thead><tr><th style="width: 10px;"></th><th style="width: 10px;"></th><th style="width: 10px;"></th>';

				$table_dummy .= '<thead><tr class="fistrow"><th colspan="2">Ergebnisse</th>';

				foreach($selected_domains as $country => $domain) {
					$table .= '<th colspan="'.count($domain).'">'.$country.'</th>';
					$table_dummy .= '<th colspan="'.count($domain).'">'.$country.'</th>';
				}
				$table .= '</tr>';
				$table_dummy .= '</tr>';

				$table .= '<tr><th style="width: 10px;">#</th><th>ISBN</th><th>VLB</th>';
				$table_dummy .= '<tr><th>ISBN</th><th>-</th>';

				foreach($selected_domains as $country => $countrydomains) {
					foreach($countrydomains as $domain) {
						$table .= '<th>'.$domain.'</th>';
						$table_dummy .= '<th>'.$domain.'</th>';
					}
				}
				$table .= '</tr></thead><tbody>';
				$table_dummy .= '</tr></thead><tbody>';
			} else {

			}
		
			$table .= '<tr class="result">';
			$table .= '<td class="">'.($i+1).'</td><td class="value">'.$isbn.'</td>';
			$table .= '<td class="value" align="center" style="vertical-align: middle;"><a href=""><img height="80" src="http://www.vlb.de/GetBlob.aspx?strDisposition=b&size=S&strIsbn='.$isbn.'"><i>
<img height="200" src="http://www.vlb.de/GetBlob.aspx?strDisposition=b&size=L&strIsbn='.$isbn.'" style="float:left;">
<table class="popup-info">
<tbody>
<tr>
<tr>
<tr>
<tr>
<tr>
<tr>
</tbody>
</table>
</i></a></td>';

			$table_dummy .= '<tr class="result">';
			$table_dummy .= '<td class="value">'.$isbn.'</td><td>Title</td>';

			foreach($selected_domains as $country => $countrydomains) {

				// Creating Target-Cells
				foreach($countrydomains as $domain) {
		
					$id = str_replace('.','_', $domain).'_'.$isbn;
					$id = str_replace('-','_', $id);
		
					$table .= '<td align="center" class="value" id="'.$id .'">-</td>';

					$table_dummy .= '<td class="value" id="dummy_'.$id .'"></td>';
			}	
		}
		$table .= '</tr>';
		$table_dummy .= '</tr>';
		
		$i ++;
		
	}
		
		$table .= '</tbody></table>';
		$table_dummy .= '</tbody></table><br>';
		

		//print($table_dummy);


		$html .= '<div id="tabs-min" class="tabs">'; 


		$html .= '<ul>';

		$html .= '<li><a href="#tabs-table">Erweiterte Ergebnisse</a></li>';
		$html .= '<li><a href="#tabs-results">Ergebnistabelle</a></li>';
		$html .= '<li><a href="#tabs-form">Eingabemaske</a></li>';

		$html .= '</ul>';

		$html .= '<div id="tabs-table"><p>'.$table.'</p></div>';
		$html .= '<div id="tabs-results"><p>'.$table_dummy.'</p></div>';

		$html .= '<div id="tabs-form"><p>'.isbnInputForm($domains, $selected_domains, $isbnlist).'</p></div>';

		$html .= '</div>'; 

		$html .= "<script>\n";




		$html .= "var xmlHttpArr = new Array();\n";


		$ajax_urls = array();


		// Creating Javascript
		$i = 1;
		foreach($isbnlist as $isbn) {
		
			foreach($selected_domains as $country => $countrydomains) {

				foreach($countrydomains as $domain) {

					$url = getArticlepageUrl($isbn, $domain);
					
					$id = str_replace('.','_', $domain).'_'.$isbn;
					$id = str_replace('-','_', $id);		
					
					$ajax_urls[] = "	'json_getprice.php?isbn=".$isbn."&domain=".$domain."'";
	
					$result_containers[] = "	".$id;
					$dummy_result_containers[] = "	dummy_".$id;
					$article_urls[] = "	'".$url."'";

				}
			}
		
		}

		$html .= "var myrequests = new Array(\n";
		$html .= implode(",\n", $ajax_urls)."\n";
		$html .= ");\n\n";


		$html .= "var result_containers = new Array(\n";
		$html .= implode(",\n", $result_containers)."\n";
		$html .= ");\n\n";

		$html .= "var dummy_result_containers = new Array(\n";
		$html .= implode(",\n", $dummy_result_containers)."\n";
		$html .= ");\n\n";

		$html .= "var article_urls = new Array(\n";
		$html .= implode(",\n", $article_urls)."\n";
		$html .= ");\n\n";


		$html .= <<<EOT


	function createXMLHTTPObject() {

		var xmlhttp = false;
		for (var i=0;i<XMLHttpFactories.length;i++) {
			try {
				xmlhttp = XMLHttpFactories[i]();
			}
			catch (e) {
				continue;
			}
			break;
		}
		return xmlhttp;
	}

	var XMLHttpFactories = [
		function () {return new XMLHttpRequest()},
		function () {return new ActiveXObject("Msxml2.XMLHTTP")},
		function () {return new ActiveXObject("Msxml3.XMLHTTP")},
		function () {return new ActiveXObject("Microsoft.XMLHTTP")}
	];

    function execAjaxRequests() {

	    xmlHttpArr = new Array();
	    for (i=0;i<myrequests.length;i++) {
		    var xmlHttpObj= createXMLHTTPObject();
			if (!xmlHttpObj){alert("Dein Browser untersützt kein AJAX");}

		    if(typeof(xmlHttpArr[i]) != 'undefined'){
			    if(xmlHttpArr[i].request != null){
			    	xmlHttpArr[i].request.transport.abort();
				    xmlHttpArr[i].request = null;
			    }
		    }
		    xmlHttpObj.open("POST",myrequests[i],true);

		    $(result_containers[i]).html('...');
		    $(dummy_result_containers[i]).html('---');

		    xmlHttpObj.onreadystatechange = parseResult;
		    xmlHttpObj.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		    xmlHttpObj.send(null);
		    xmlHttpArr.push(xmlHttpObj);
	    }
    }

    function parseResult(){

	    for(i=0;i<xmlHttpArr.length;i++){
		    if(xmlHttpArr[i].readyState==4){
			    if (xmlHttpArr[i].status==200) {
				var resp = xmlHttpArr[i].responseText;

				var myObject = eval('(' + resp + ')');


				if(myObject['value'] != undefined) {

					if(myObject['seostatus'] == 'notice') {
					
						$(result_containers[i]).addClass('notice');
						
					}
				
					$(result_containers[i]).html('<a target=\"_blank\" href=\"'+article_urls[i]+'\"><img height=\"80\" src=\"'+myObject['image']+'\"><br><i><img style="float:left;" height=\"200\" src=\"'+myObject['image']+'\"><table class="popup-info"><tr><td>Domain</td><td>'+myObject['domain']+'</td></tr><tr><td>Titel</td><td>'+myObject['title']+'</td></tr><tr><td>Subtitel</td><td>'+myObject['subtitle']+'</td></tr><tr><td>Text</td><td>'+myObject['text']+'</a></i></td></tr><tr><td>Autor</td><td>'+myObject['author']+'</td></tr><tr><td>Preis</td><td>'+myObject['value']+' '+myObject['currency']+'</td></tr></table>');
					
					$(dummy_result_containers[i]).html('<table><tr><td>T: '+myObject['title']+'</tD></tr><tr><td>S: '+myObject['subtitle']+'</tD></tr><tr><td>Text: '+myObject['text']+'</td></tr><tr><td>Keywords:<br> '+myObject['keywords']+'</td></tr><tr><td>P: '+myObject['value']+' '+myObject['currency']+'</td></tr></table>');

				} else {
					$(result_containers[i]).html('n.a.');
					$(dummy_result_containers[i]).html('n.a.');
				}	
			    }
		    }
	    }
    }

	execAjaxRequests();

EOT;
	$html .= "</script>\n";




	print($html);
	
	if(count($isbnlist) > $ISBN_COUNT_LIMIT){
		
		//echo usermessage('error', 'Sie haben mehr als '.$ISBN_COUNT_LIMIT.' ISBN Nummern eingegeben, es werden nur die ersten '.$ISBN_COUNT_LIMIT.' Nummern verwendet.');

		
	} else {

	}
}





include('inc/footer.php');
die();

?>