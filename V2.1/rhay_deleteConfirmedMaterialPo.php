<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_wholeNumber.php');
include('PHP Modules/anthony_retrieveText.php');
include('PHP Modules/gerald_functions.php');
include('PHP Modules/rose_prodfunctions.php');
ini_set("display_errors", "on");

$tpl = new PMSTemplates;

$tpl->setDataValue("L609"); // Delete
$tpl->setAttribute("type","submit");
$tpl->setAttribute("name","submitReason");
$tpl->setAttribute("id","submitReason");
$tpl->setAttribute("form","deleteForm");
$buttonDelete = $tpl->createButton();

?>
<form method="POST" action='gerald_poPreparationList.php' id="deleteForm"></form>
<center>
	<label><?php echo displayText('L214');?></label><br>
	<textarea name="reason" id="reason" form="deleteForm" required></textarea><br>
	<input type="hidden" name="lote" form="deleteForm" value="<?php echo $_GET['lote']?>">
	<input type="hidden" name="dateNeeded" form="deleteForm" value="<?php echo $_GET['dateNeeded']?>">
	<?php echo $buttonDelete; ?>
	<!-- <input type="submit" name="submitReason" id="submitReason" form="deleteForm" value="Delete"> -->
</center>
