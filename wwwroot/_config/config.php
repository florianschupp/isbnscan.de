<?php

// Definiert, ob üerhaupt Mails versendet werden sollen. Kann z.B. zu Testzwecken deaktiviert werden
$SEND_EMAILS = true;

$ISBN_COUNT_LIMIT = 20;

// Definiert, ob im Sende-Modus ein Button erscheint, der den Import der Daten in den "Sent" Ordner ohne Mailversand erlaubt
// Sinnvoll zu Testzwecken, oder um Alt-Daten ins System zu importieren
$ALLOW_IMPORT_ONLY = false;

// An diese E-Mail Adresse wird standardmŠ§ig immer eine BCC Kopie versendet
//$MAIL_TO_BCC = 'Buchhaltung@pubbles.de';

// HTML-E-Mail Template
$BOILERPLATE_FILE = 'inc/boilerplate/email.html';

// Where to store loaded Exchangerates
$CURRENCY_FILES['CHF']['cache_dir'] = '_data/exchangerates/';
//$CURRENCY_FILES['USD']['cache_dir'] = '_data/exchangerates/';

$CURRENCY_FILES['CHF']['url'] = 'http://www.bundesbank.de/cae/servlet/StatisticDownload?tsId=BBEX3.M.CHF.EUR.BB.AC.A02&its_csvFormat=de&its_fileFormat=csv&mode=its';
//$CURRENCY_FILES['USD']['url'] = 'http://www.bundesbank.de/cae/servlet/StatisticDownload?tsId=BBEX3.M.USD.EUR.BB.AC.A02&its_csvFormat=de&its_fileFormat=csv&mode=its';

// FŸr EUR muss keine URL definiert werden, der Kurs ist immer 1
$CURRENCY_FILES['EUR'] = array();

//allgemeine Kommission; hier ist das Enddatum einzutragen!
$COMMISSION_CONFIG['default']['2013-01-01'] = 0.035;
$COMMISSION_CONFIG['default']['2013-06-30'] = 0.032;
$COMMISSION_CONFIG['default']['2014-02-28'] = 0.021;
$COMMISSION_CONFIG['default']['2014-06-30'] = 0.018;

//$COMMISSION_CONFIG['10']['2013-01-01'] = 0.10;
//$COMMISSION_CONFIG['10']['2013-02-01'] = 'default';
//Haendlerspezifische Provision
$COMMISSION_CONFIG['60']['2014-06-01'] = 0.03;

$TAX_CONFIG['DE']['2013-01-01'] = 0.19;
$TAX_CONFIG['AT']['2013-01-01'] = 0.2;
$TAX_CONFIG['CH']['2013-01-01'] = 0.08;
$TAX_CONFIG['BE']['2013-01-01'] = 0.20;
$TAX_CONFIG['NL']['2013-01-01'] = 0.19;

// Definition der Farebn fŸr die Charts
$CHART_COLORS = array('#FF6600', '#FCD202', '#B0DE09', '#0D8ECF', '#2A0CD0', '#CD0D74', '#CC0000', '#00CC00', '#0000CC', '#DD00DD', '#009999', '#33FF99', '#990000','#006600');
$CHART_COLORS_JSARRAY = "'".implode("', '", $CHART_COLORS)."'"; // Umwandlung in JS Array

//Setting the directories
$DIR_010_TELEKOM_INCOMING = 	"../010_TELEKOM_INCOMING/";
$DIR_020_TELEKOM_PROCESSING = 	"../020_TELEKOM_PROCESSING/";
$DIR_030_ZIP_PROCESSING = 		"../030_ZIP_PROCESSING/";
$DIR_040_TELEKOM_IMPORT_ARCHIVE = 	"../040_TELEKOM_IMPORT_ARCHIVE/";
$DIR_050_IMPORT_DIR = 		"../050_PALI_IMPORT/";
$DIR_060_IMPORTED_FILES =		"../060_IMPORTED_FILES/";
$DIR_070_SENT_FILES	=		"../070_SENT_FILES/";
$DIR_900_FAILED_IMPORTS = 		"../900_FAILED_IMPORTS/";
$DIR_990_TRASH = 			"../990_TRASH/";

$DIR_100_ISBN_INDEX = 		"../100_ISBN_INDEX/";

$TODO_FILE = '_data/todos.txt';
$USERS_FILE = '_data/users.txt';


//$MESSAGEFILES['default'] = '_data/message_default.txt';
$MESSAGEFILES['kreditor'] = '_data/message_kreditor.txt';
$MESSAGEFILES['debitor'] = '_data/message_debitor.txt';

// Managers File
$ACCOUNTMANAGER_FILE = '_data/accountmanager_configuration.txt';

// Partner File
$PARTNERS_FILE = '_data/partner_configuration.txt';

$HOSTNAME_PRODUKTION = 'wadjet.ispgateway.de';
$HOSTNAME_STAGING = 'pubcms8302';


$known_domains = array();

$known_domains[] = 'weltbild.de';
$known_domains[] = 'weltbild.at';
$known_domains[] = 'weltbild.ch';
$known_domains[] = 'hugendubel.de';

$known_domains[] = 'derclub.de';
$known_domains[] = 'donauland.at';
$known_domains[] = 'otto-media.de';

$known_domains[] = 'buecher.de';
$known_domains[] = 'ebooks.bild.de';

$known_domains[] = 'buch.ch';
$known_domains[] = 'buch.de';

$known_domains[] = 'thalia.de';
$known_domains[] = 'thalia.at';
$known_domains[] = 'thalia.ch';

$known_domains[] = 'pageplace.de';

require('_config/config_partner.php');


?>
