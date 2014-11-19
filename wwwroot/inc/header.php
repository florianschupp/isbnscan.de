<?php
if(isset($_GET["theme"])) {
	$theme = $_GET["theme"];
} else {

	if(array_key_exists('theme', $_SESSION)) {
		$theme = $_SESSION["theme"];
	} else {
		$theme = 'dark';
	}
}
 header("Content-Type: text/html; charset=utf-8");
?>
<html>
<head>
<meta name="robots" content="noindex">
<meta http-equiv="Content-type" content="text/html;charset=utf-8">

<link rel="shortcut icon" href="favicon.ico">

<!--<link rel="stylesheet" href="css/login.css" type="text/css" media="screen,projection,print">-->
<link rel="stylesheet" href="css/style.css" type="text/css" media="screen,projection,print">
<link rel="stylesheet" href="css/iconfonts.css" type="text/css" media="screen,projection,print">
<link rel="stylesheet" href="css/menu.css" type="text/css" media="screen,projection,print">
<link rel="stylesheet" href="css/infobox.css" type="text/css" media="screen,projection,print">
<link rel="stylesheet" href="css/print.css" type="text/css" media="print">
<link href='https://fonts.googleapis.com/css?family=Maven+Pro:400,500,700' rel='stylesheet' type='text/css'>
<link href='https://fonts.googleapis.com/css?family=PT+Sans+Narrow' rel='stylesheet' type='text/css'>


<script type='text/javascript' language="javascript" src="/js/functions.js"></script>
<script type="text/javascript" language="javascript" src="jquery/js/jquery-1.9.1.js"></script>
<script type="text/javascript" language="javascript" src="jquery/js/jquery-ui-1.10.3.custom.js"></script>

<?php

$ua = browser_info();
if(array_key_exists('msie', $ua)) {

	echo usermessage('error', '<b>Wichtig:</b> Ihr Browser wird nicht unterst&uuml;tzt! Bitte verwenden Sie Firefox oder Safari, ansonsten kann es zu Darstellungsproblemen kommen!');

}

if(isset($custom_header)) {

	print $custom_header;
}

?>

<link rel="stylesheet" href="jquery/css/ui.css" type="text/css" media="screen,projection,print">

<?php
if(isset($html_head)) {
	
	echo $html_head;
	
}

if(!isset($pagetitle)) $pagetitle = '';
echo'<title>Florian Schupp - ISBN Scanner - '.$pagetitle.'</title>';

?>


</head>
<body class="<?php echo $theme; ?>">
<div id="container">

<?php

if(isset($beta)) {
	echo '<img style="float: left;" width="100" src="img/beta.png">';
}

include('inc/top_menu.php');
?>
<div id="loading" style="display: none;">
	<div id="content">
		<span class="expand"></span>
	</div>
</div>

<div id="basecontent">
