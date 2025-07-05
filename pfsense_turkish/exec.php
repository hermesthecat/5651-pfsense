<?php
/* $Id$ */
/*
	Exec+ v1.02-000 - Copyright 2001-2003, All rights reserved
	Created by technologEase (http://www.technologEase.com).

	(modified for m0n0wall by Manuel Kasper <mk@neon1.net>)
*/

require("guiconfig.inc");

if (($_POST['submit'] == "Download") && !empty($_POST['dlPath'])) {
	// SECURITY: Path traversal protection
	$requested_path = $_POST['dlPath'];
	
	// Define allowed directories
	$allowed_directories = [
		'/tmp/',
		'/var/log/',
		'/var/tmp/',
		'/usr/local/www/'
	];
	
	// Normalize the path
	$real_path = realpath($requested_path);
	
	// Check if file exists and path is valid
	if (!$real_path || !file_exists($real_path)) {
		echo "ERROR: File not found or invalid path.";
		exit;
	}
	
	// Check if the real path is within allowed directories
	$path_allowed = false;
	foreach ($allowed_directories as $allowed_dir) {
		if (strpos($real_path, $allowed_dir) === 0) {
			$path_allowed = true;
			break;
		}
	}
	
	if (!$path_allowed) {
		echo "SECURITY ERROR: Access denied. File path not in allowed directories.\n";
		echo "Allowed directories: " . implode(', ', $allowed_directories) . "\n";
		echo "Requested path: " . htmlspecialchars($requested_path) . "\n";
		exit;
	}
	
	// Additional security check: prevent access to sensitive files
	$dangerous_files = ['.passwd', '.shadow', 'config.xml', '.htaccess', '.htpasswd'];
	$filename = basename($real_path);
	foreach ($dangerous_files as $dangerous_file) {
		if (strpos($filename, $dangerous_file) !== false) {
			echo "SECURITY ERROR: Access to sensitive file denied.";
			exit;
		}
	}
	
	session_cache_limiter('public');
	$fd = fopen($real_path, "rb");
	if (!$fd) {
		echo "ERROR: Could not open file.";
		exit;
	}
	
	header("Content-Type: application/octet-stream");
	header("Content-Length: " . filesize($real_path));
	header("Content-Disposition: attachment; filename=\"" .
		trim(htmlentities(basename($real_path))) . "\"");

	fpassthru($fd);
	fclose($fd);
	exit;
} else if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
	move_uploaded_file($_FILES['ulfile']['tmp_name'], "/tmp/" . $_FILES['ulfile']['name']);
	$ulmsg = "Uploaded file to /tmp/" . htmlentities($_FILES['ulfile']['name']);
	unset($_POST['txtCommand']);
}

if($_POST)
	conf_mount_rw();

// Function: is Blank
// Returns true or false depending on blankness of argument.

function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }


// Function: Puts
// Put string, Ruby-style.

function puts( $arg ) { echo "$arg\n"; }


// "Constants".

$Version    = '';
$ScriptName = $HTTP_SERVER_VARS['SCRIPT_NAME'];

// Get year.

$arrDT   = localtime();
$intYear = $arrDT[5] + 1900;

$pgtitle = "Tanımlama: Komut Çalıştır";
include("head.inc");
?>

<script language="javascript">
<!--

   // Create recall buffer array (of encoded strings).

<?php

if (isBlank( $_POST['txtRecallBuffer'] )) {
   puts( "   var arrRecallBuffer = new Array;" );
} else {
   puts( "   var arrRecallBuffer = new Array(" );
   $arrBuffer = explode( "&", $_POST['txtRecallBuffer'] );
   for ($i=0; $i < (count( $arrBuffer ) - 1); $i++) puts( "      '" . $arrBuffer[$i] . "'," );
   puts( "      '" . $arrBuffer[count( $arrBuffer ) - 1] . "'" );
   puts( "   );" );
}

?>

   // Set pointer to end of recall buffer.
   var intRecallPtr = arrRecallBuffer.length-1;

   // Functions to extend String class.
   function str_encode() { return escape( this ) }
   function str_decode() { return unescape( this ) }

   // Extend string class to include encode() and decode() functions.
   String.prototype.encode = str_encode
   String.prototype.decode = str_decode

   // Function: is Blank
   // Returns boolean true or false if argument is blank.
   function isBlank( strArg ) { return strArg.match( /^\s*$/ ) }

   // Function: frmExecPlus onSubmit (event handler)
   // Builds the recall buffer from the command string on submit.
   function frmExecPlus_onSubmit( form ) {

      if (!isBlank(form.txtCommand.value)) {
		  // If this command is repeat of last command, then do not store command.
		  if (form.txtCommand.value.encode() == arrRecallBuffer[arrRecallBuffer.length-1]) { return true }

		  // Stuff encoded command string into the recall buffer.
		  if (isBlank(form.txtRecallBuffer.value))
			 form.txtRecallBuffer.value = form.txtCommand.value.encode();
		  else
			 form.txtRecallBuffer.value += '&' + form.txtCommand.value.encode();
	  }

      return true;
   }

   // Function: btnRecall onClick (event handler)
   // Recalls command buffer going either up or down.
   function btnRecall_onClick( form, n ) {

      // If nothing in recall buffer, then error.
      if (!arrRecallBuffer.length) {
         alert( 'Nothing to recall!' );
         form.txtCommand.focus();
         return;
      }

      // Increment recall buffer pointer in positive or negative direction
      // according to <n>.
      intRecallPtr += n;

      // Make sure the buffer stays circular.
      if (intRecallPtr < 0) { intRecallPtr = arrRecallBuffer.length - 1 }
      if (intRecallPtr > (arrRecallBuffer.length - 1)) { intRecallPtr = 0 }

      // Recall the command.
      form.txtCommand.value = arrRecallBuffer[intRecallPtr].decode();
   }

   // Function: Reset onClick (event handler)
   // Resets form on reset button click event.
   function Reset_onClick( form ) {

      // Reset recall buffer pointer.
      intRecallPtr = arrRecallBuffer.length;

      // Clear form (could have spaces in it) and return focus ready for cmd.
      form.txtCommand.value = '';
      form.txtCommand.focus();

      return true;
   }
//-->
</script>
<style>
<!--

input {
   font-family: courier new, courier;
   font-weight: normal;
   font-size: 9pt;
}

pre {
   border: 2px solid #435370;
   background: #F0F0F0;
   padding: 1em;
   font-family: courier new, courier;
   white-space: pre;
   line-height: 10pt;
   font-size: 10pt;
}

.label {
   font-family: tahoma, verdana, arial, helvetica;
   font-size: 11px;
   font-weight: bold;
}

.button {
   font-family: tahoma, verdana, arial, helvetica;
   font-weight: bold;
   font-size: 11px;
}

-->
</style>
</head>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<p class="pgtitle"><?=$pgtitle?></p>
<?php if (isBlank($_POST['txtCommand'])): ?>
<p class="red"><strong>Not: Bu fonksiyon desteklenmemektedir. Eğer kullanmaya devam ederseniz sorumluluk size aittir.
</strong></p>
<?php endif; ?>
<?php if ($ulmsg) echo "<p><strong>" . $ulmsg . "</strong></p>\n"; ?>
<?php

if (!isBlank($_POST['txtCommand'])) {
   puts("<pre>");
   
   // SECURITY: Input validation and command whitelisting
   $allowed_commands = [
       'ps', 'ps aux', 'ps -ef',
       'netstat', 'netstat -an', 'netstat -rn',
       'ifconfig', 'ifconfig -a',
       'df', 'df -h',
       'free', 'free -h',
       'uptime',
       'date',
       'who', 'w',
       'top -n 1',
       'uname', 'uname -a',
       'ls', 'ls -la',
       'pwd'
   ];
   
   $command = trim($_POST['txtCommand']);
   $command_found = false;
   
   foreach ($allowed_commands as $allowed_cmd) {
       if ($command === $allowed_cmd || strpos($command, $allowed_cmd . ' ') === 0) {
           $command_found = true;
           break;
       }
   }
   
   if (!$command_found) {
       echo "SECURITY ERROR: Command not allowed.\n";
       echo "Allowed commands: " . implode(', ', $allowed_commands) . "\n";
       echo "Requested command: " . htmlspecialchars($command) . "\n";
   } else {
       puts("\$ " . htmlspecialchars($command));
       putenv("PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin");
       
       // Use escapeshellcmd for additional security
       $safe_command = escapeshellcmd($command);
       $ph = popen($safe_command, "r");
       
       if ($ph) {
           while ($line = fgets($ph)) {
               echo htmlspecialchars($line);
           }
           pclose($ph);
       } else {
           echo "ERROR: Could not execute command.\n";
       }
   }
   
   puts("</pre>");
}


if (!isBlank($_POST['txtPHPCommand'])) {
   puts("<pre>");
   // SECURITY: eval() function removed due to PHP code injection vulnerability
   // This functionality allowed arbitrary PHP code execution
   echo "PHP command execution has been disabled for security reasons.\n";
   echo "Security vulnerability: PHP Code Injection via eval()\n";
   echo "Contact system administrator for alternative solutions.\n";
   puts("</pre>");
}


?>
<div id="niftyOutter">
<form action="exec.php" method="POST" enctype="multipart/form-data" name="frmExecPlus" onSubmit="return frmExecPlus_onSubmit( this );">
  <table>
	<tr>
	  <td colspan="2" valign="top" class="vnsepcell">Bir kabuk komutu çalıştır</td>
	</tr>  
    <tr>
      <td class="label" align="right">Komut:</td>
      <td class="type"><input id="txtCommand" name="txtCommand" type="text" size="80" value="<?=htmlspecialchars($_POST['txtCommand']);?>"></td>
    </tr>
    <tr>
      <td valign="top">&nbsp;&nbsp;&nbsp;</td>
      <td valign="top" class="label">
         <input type="hidden" name="txtRecallBuffer" value="<?=$_POST['txtRecallBuffer'] ?>">
         <input type="button" class="button" name="btnRecallPrev" value="<" onClick="btnRecall_onClick( this.form, -1 );">
         <input type="submit" class="button" value="Execute">
         <input type="button" class="button" name="btnRecallNext" value=">" onClick="btnRecall_onClick( this.form,  1 );">
         <input type="button"  class="button" value="Clear" onClick="return Reset_onClick( this.form );">
      </td>
    </tr>
	<tr>
	  <td colspan="2" valign="top" height="16"></td>
	</tr>
	<tr>
	  <td colspan="2" valign="top" class="vnsepcell">İndir</td>
	</tr>    
    <tr>
      <td align="right">İndirilecek dosya:</td>
      <td>
        <input name="dlPath" type="text" id="dlPath" size="50">
	</td></tr>
    <tr>
      <td valign="top">&nbsp;&nbsp;&nbsp;</td>
      <td valign="top" class="label">	
        <input name="submit" type="submit"  class="button" id="download" value="İndir">
        </td>
    </tr>
	<tr>
	  <td colspan="2" valign="top" height="16"></td>
	</tr>
	<tr>
	  <td colspan="2" valign="top" class="vnsepcell">Yükle</td>
	</tr>    
    <tr>
      <td align="right">Dosya yükle:</td>
      <td valign="top" class="label">
	<input name="ulfile" type="file" class="button" id="ulfile">
	</td></tr>
    <tr>
      <td valign="top">&nbsp;&nbsp;&nbsp;</td>
      <td valign="top" class="label">	
        <input name="submit" type="submit"  class="button" id="upload" value="Yükle"></td>
    </tr>
	<tr>
	  <td colspan="2" valign="top" height="16"></td>
	</tr>
	<tr>
	  <td colspan="2" valign="top" class="vnsepcell">PHP komutu çalıştırma</td>
	</tr>
	<tr>
		<td align="right">Komut:</td>
		<td class="type"><textarea id="txtPHPCommand" name="txtPHPCommand" type="text" rows="3" cols="50"><?=htmlspecialchars($_POST['txtPHPCommand']);?></textarea></td>
	</tr>
    <tr>
      <td valign="top">&nbsp;&nbsp;&nbsp;</td>
      <td valign="top" class="label">
         <input type="submit" class="button" value="Çalıştırma">
	 <p>
	 <strong>Örnek:</strong>   interfaces_carp_bring_up_final();
      </td>
    </tr>
    
  </table>
</div>
<?php include("fend.inc"); ?>
</form>
<script language="Javascript">
document.forms[0].txtCommand.focus();
</script>
</body>
</html>

<?php

if($_POST)
	conf_mount_ro();

?>
