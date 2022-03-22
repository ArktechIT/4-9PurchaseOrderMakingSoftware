
<?php
$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
$javascriptLib = "/V3/Common Data/Libraries/Javascript/";
$templates = "/V3/Common Data/Templates/";
set_include_path($path);	
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_retrieveText.php');
ini_set("display_errors", "on");

?>
<form method="POST" id="deleteForm"></form>
<center>
	<label><?php echo displayText('L214');?></label><br>
	<textarea name="reason" id="reason" form="deleteForm" required></textarea><br>
	<input type="hidden" name="lote" form="deleteForm" value="<?php echo $_GET['lote']?>">
	<input type="hidden" name="dateNeeded" form="deleteForm" value="<?php echo $_GET['dateNeeded']?>">
	<input type="submit" name="submitReason" id="submitReason" form="deleteForm" value="Delete">
</center>
