<?php
require_once('_functions/functions.php');

$start = microtime(true);

include('inc/header.php');

checkrights('user', $USERDATA);


echo'<ol class="tree">';
listSentJobs('Debitor', 'sent'); 
echo'</ol>';

$end = microtime(true);
$parsetime = ($end - $start);

unset($end);
unset($start);

include('inc/footer.php');
die();


?>