<?php
require_once('_functions/base_functions.php');
session_start();
//var_dump($_SESSION);
$a =& $_SESSION;
unset($_SESSION);
$a['x'] = 1; // $a refers to the original $_SESSION
$_SESSION['x'] = 2; // new unrelated array

//print_r($_SESSION['username']);


//unset($_SESSION['username']);
session_unset();

session_write_close();
// saved session contains x => 1

//die();

header('Location: login.php');
?>