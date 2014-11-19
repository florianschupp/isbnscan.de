<?php
global $parsetime;
?>

	</div>
</div>
</div>

<div id="footer">Laufzeit: 
<?php 
echo round($parsetime,2).' Sekunden - (Session started: '.date('Y-m-d H:i:s', $_SESSION['starttime']).' Dauer: '.(time()-$_SESSION['last_access']).') '; 
echo ' Ihre IP Adresse: '.$_SERVER['REMOTE_ADDR'].' '; 
echo ' &copy; 2013 - '.date(Y).' Florian Schupp '; 

?></div>

</body>
</html>

<?php

	$_SESSION['last_url'] = curPageURL();
	$_SESSION['last_access'] = time();
?>


