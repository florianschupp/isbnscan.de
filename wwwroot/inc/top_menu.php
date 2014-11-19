<div id='cssmenu' class="no-print">
<ul>
	<li class='has-sub'><a href="home.php"><span class="entypo-home"></span><span class="menu-label" style="display: none;">Home</span></a>
            <ul>
				<li><a href="logs.php"><span>Errorlog</span></a></li>
				<li><a href="logs.php?log=actions"><span>Actionlog</span></a></li>
	     </ul>
	</li>


	<li class='has-sub'><a href="#"><span class="entypo-tools"></span><span class="menu-label">Tools</span></a>
	<ul>

<?php 
//if(hasrights('pricecrawler', $USERDATA)) {

$str = <<<EOT
		<li><a href="pricegrabber.php"><span class="entypo-tag"></span><span class="menu-label">Preisvergleich</span></a></li>

EOT;
echo $str;
//}


?>

	</ul>
	</li>


<?php

if(hasrights('billing', $USERDATA) OR hasrights('admin', $USERDATA)) {

$str = <<<EOT
	<li class='has-sub'><a href="#"><span class="fontawesome-cogs active"></span><span class="menu-label">Konfiguration</span></a>
		<ul>
			<li class='has-sub'><a href="list_users.php"><span class="entypo-users"></span>&nbsp;<span>Benutzer Stammdaten</span></a>
    	       	<ul>
					<li><a href="list_users.php"><span class="entypo-list"></span>&nbsp;Benutzer anzeigen</a></li>
					<li><a href="adduser.php"><span class="entypo-user-add"></span>&nbsp;Benutzer anlegen</a></li>
				</ul>
			</li>

		</ul>
	</li>


<!--	
<li class="has-sub">

<a>&nbsp;

<input id="autocomplete" type="text" name="q" placeholder="Suche..." />

<script>
$( "#autocomplete" ).autocomplete({

	search  : function(){
		$(this).addClass('working');
		$("#ui-id-1").hide();
	},
	open    : function(){
		$(this).removeClass('working');
	},

	source: "json_autocomplete.php",
	select: function( event, ui ) {
		
	  $("#loading").show();
	  $("#basecontent").hide();
		
	  if(ui.item.url != '') {
	  
	      window.location.href = ui.item.url;
	  }
    },

});


</script>
</a>
</li>
-->

EOT;
echo $str;
}
?>

</ul>


<ul style="float: right;">

<?php 

if(hasrights('admin', $USERDATA)) {

$str = <<<EOT


	<li class='has-sub'><a href=""><span class="entypo-graduation-cap"></span><span class="menu-label">Admin</span></a>
	
		<ul>
			<li><a href="backup.php"><span class="entypo-floppy"></span>&nbsp;Backup erzeugen</a></li>
			<li><a href="update.php"><span class="entypo-floppy"></span>&nbsp;Update durchf&uuml;hren</a></li>
			<li><a href="clear_cachedata.php"><span class="entypo-user-add"></span>&nbsp;Cache löschen (Vorsicht!)</a></li>
			<li><a href="edit_todos.php"><span class="entypo-list"></span>&nbsp;ToDos bearbeiten</a></li>		
		</ul>
			
	</li>
EOT;

echo $str;
}
?>

	<li class='has-sub'><a href=""><span class="entypo-user"></span><span class="menu-label">Konto</span></a>
	
		<ul>
			
			<li><a href="change_password.php"><span class="entypo-vcard"></span>&nbsp;<?php echo $_SESSION['username']; ?></a></li>
			<li><a href="change_password.php"><span class="entypo-key"></span>&nbsp;Passwort &auml;ndern</a></li>
			<li><a href="logout.php"><span class="entypo-logout"></span>&nbsp;Logout</a></li>
		
		</ul>
			
	</li>


<?
	if($theme=='dark') {
		echo'	<li><a href="'.setURLParam(array('theme','light')).'" title="Tagmodus"><span class="entypo-lamp"></span></a></li>';
	} else {
		echo'	<li><a href="'.setURLParam(array('theme','dark')).'" title="Nachtmodus"><span class="entypo-moon"></span></a></li>';
	}
?>	

	<li><a href="logout.php" title="Logout"><span class="entypo-logout"></span></a></li>	

</ul>
</div>