<?php

$partner_structure = array();

$partner_structure['id']['type'] = 'String';
$partner_structure['id']['maxlength'] = 6;
$partner_structure['id']['minlength'] = 1;
//$partner_structure['id']['required'] = true;
$partner_structure['id']['unique'] = true;
$partner_structure['id']['info'] = 'Identifikation des Geschäftspartners, Shop-ID (Retailer) oder Publisher-ID (DTXXX, Lieferant)';

$partner_structure['partnertype']['type'] = 'Set';
$partner_structure['partnertype']['values'] = array('Debitor' => 'Debitor', 'Kreditor' => 'Kreditor');
$partner_structure['partnertype']['info'] = 'Geschäftspartnertyp';

$partner_structure['billing']['type'] = 'Set';
$partner_structure['billing']['values'] = array('Gutschrift' => 'Gutschrift', 'Rechnung' => 'Rechnung');
$partner_structure['billing']['info'] = 'Abrechnungsweise';

$partner_structure['tier']['type'] = 'Set';
$partner_structure['tier']['values'] = array('0' => 'Retailer', '1' => 'RCDP', '2' => 'MC Anlieferung durch Verlag', '3' => 'MC Anlieferung durch Aggregator', '4' => 'Remote Digital Warehouse');
$partner_structure['tier']['info'] = 'Art der Auslieferung, 0: Retailer, 1: RCDP, 2: MC Anlieferung durch Verlag, 3: MC Anlieferung durch Aggregator, 4: Remote Digital Warehouse';

$partner_structure['prio']['type'] = 'String';
$partner_structure['prio']['maxlength'] = 3;
$partner_structure['prio']['info'] = 'Priorisierung innerhalb eines "Tiers"';

$partner_structure['name']['type'] = 'String';
$partner_structure['name']['maxlength'] = 64;
$partner_structure['name']['info'] = 'Domain des Retailers oder Name des Lieferanten der normalerweise auch auf der Rechnung erscheint.';

$partner_structure['to_emails']['type'] = 'Emails';
$partner_structure['to_emails']['info'] = 'E-Mail Adressen, getrennt mit Strichpunkt / Semikolon(";")';

$partner_structure['anrede']['type'] = 'String';
$partner_structure['anrede']['maxlength'] = 32;
$partner_structure['anrede']['info'] = 'Vollständige Anrede des Ansprechpartners. Dieser Wert wird bei Textgenerierung in E-Mails verwendet (z.B.: "Sehr geehrte Frau")';

$partner_structure['contactperson_first']['type'] = 'String';
$partner_structure['contactperson_first']['maxlength'] = 32;
$partner_structure['contactperson_first']['info'] = 'Vorname des Ansprechpartners';

$partner_structure['contactperson_last']['type'] = 'String';
$partner_structure['contactperson_last']['maxlength'] = 32;
$partner_structure['contactperson_last']['info'] = 'Nachname des Ansprechpartners';

$partner_structure['accountmanager']['type'] = 'Set';
$partner_structure['accountmanager']['values'] = $am_ar;
$partner_structure['accountmanager']['info'] = 'Initialen (ID) des Accountmanagers, wird zur Generierung von E-Mails (Fusszeile, E-Mail Absender, Antwortadresse) verwendet';

$partner_structure['status']['type'] = 'Set';
$partner_structure['status']['values'] = array('0' => 'in Anbindung', '-1' => 'deaktiviert', 'live' => 'Live');
$partner_structure['status']['info'] = 'Wird zur Anzeige in Übersichten verwendet (0: In Anbindung, live: Aktiv, -1: beendet)';

$partner_structure['rhythmus']['type'] = 'Set';
$partner_structure['rhythmus']['values'] = array('M' => 'Monatlich', 'Q' => 'Je Quartal', 'J' => 'J&auml;hrlich');
$partner_structure['rhythmus']['info'] = 'Abrechnungsrhythmus, M: Monat, Q: Quartal, J: Jahr';

$partner_structure['group']['type'] = 'String';
$partner_structure['group']['maxlength'] = 32;
$partner_structure['group']['info'] = 'Gruppierungsmerkmal für "Periodenansicht gruppiert", z.B.: Gruppierung aller Retailer eines Mandanten';

$partner_structure['editx']['type'] = 'Set';
$partner_structure['editx']['values'] = array('0' => 'Nein', '1' => 'EDItx Versand aktiv');
$partner_structure['editx']['info'] = 'EDItx Versand für diesen Partner aktivieren?';

?>