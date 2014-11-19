<?php

$partner_structure = array();

$user_structure['username']['type'] = 'String';
$user_structure['username']['maxlength'] = 64;
$user_structure['username']['minlength'] = 4;
//$user_structure['username']['required'] = true;
$user_structure['username']['unique'] = true;
$user_structure['username']['info'] = 'Benutzername / Login';

$user_structure['passwordhash']['type'] = 'String';
$user_structure['passwordhash']['maxlength'] = 128;
$user_structure['passwordhash']['info'] = 'passwordhash';

$user_structure['email']['type'] = 'String';
$user_structure['email']['maxlength'] = 64;
$user_structure['email']['info'] = 'E-Mail Adresse';

$user_structure['roles']['type'] = 'String';
$user_structure['roles']['maxlength'] = 128;
$user_structure['roles']['info'] = 'Berechtigungen (getrennt durch Komma)';

$user_structure['status']['type'] = 'Set';
$user_structure['status']['values'] = array('inactive' => 'inactive', 'active' => 'active', 'initialpassword' => 'initialpassword');
$user_structure['status']['info'] = 'Status';

$user_structure['lastlogin']['type'] = 'String';
$user_structure['lastlogin']['maxlength'] = 32;
$user_structure['lastlogin']['info'] = 'lastlogin';

$user_structure['failedlogins']['type'] = 'String';
$user_structure['failedlogins']['maxlength'] = 32;
$user_structure['failedlogins']['info'] = 'failedlogins';
?>