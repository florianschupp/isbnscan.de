<?php
require_once('_functions/functions.php');

checkrights('user', $USERDATA);

header('Location: pricegrabber.php');

die();

?>