<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/gerald_functions.php');
include('PHP Modules/anthony_retrieveText.php');
ini_set("display_errors","on");

$lote = $_GET['lotNumber'];
$dateNeeded = $_GET['dateNeeded'];

$x = 0;

?>
<style type="text/css">
/*	
#infoTable tbody {
    display:block;
    height:400px;
    overflow:auto;
}
#infoTable thead, tbody tr {
    display:table;
    width:100%;
    table-layout:fixed;/* even columns width , fix width of table too*/
}
#infoTable thead {
    width: calc( 100% - 1em )/* scrollbar is average 1em/16px width, remove it from thead width */
}
#infoTable table {
    width:400px;
}*/
</style>
<?php
if($dateNeeded != "")
{
	echo "<br><center><table border=1 id='infoTable'> ";
	echo "<thead>";
	echo "<th style='width:50px;'>".displayText('L843')."</th>";
	echo "<th>".displayText('L45')."</th>";
	echo "<th>Working Qty</th>";
	echo "<th>Requirements</th>";
	echo "</thead>";
	echo "<tbody>";
	$sql = "SELECT * FROM  `ppic_materialcomputation` WHERE lotNumber = '".$lote."'";
	$process = $db->query($sql);
	if($process->num_rows > 0)
	{
		$result = $process->fetch_assoc();
		$mcId = $result['materialComputationId']; //materialComputationId

		$sql1 = "SELECT * FROM  `ppic_materialcomputationdetails` WHERE  `materialComputationId` =".$mcId;
		$process1 = $db->query($sql1);
		if($process1->num_rows > 0)
		{
			while($result1 = $process1->fetch_assoc())
			{
				echo 
				"<tr>
					<td style='width:50px;'>".++$x."</td>
					<td>".$result1['lotNumber']."</td>
					<td>".$result1['workingQuantity']."</td>
					<td>".$result1['requirement']."</td>
				</tr>";
			}
		}

	}
	echo "</tbody>";
	echo "</table></center>";
}
else
{
	$sql = "SELECT * FROM system_confirmedmaterialpo WHERE lotNumber = '".$lote."'";
	$process = $db->query($sql);
	if($process->num_rows > 0)
	{
		$result = $process->fetch_assoc();

		$sql1 = "SELECT firstName,surName FROM hr_employee WHERE idNumber = '".$result['employeeId']."'";
		$process1 = $db->query($sql1);
		$result1 = $process1->fetch_assoc();



		echo "<center><h2>Manually Command</h2></center>";
		echo "<p><b>Lot Number :</b> ".$result['lotNumber']."<br>";
		echo "<b>Date 	:</b>	".$result['dateAdded']."<br>";
		echo "<b>Employee :</b>". $result1['firstName']." ".$result1['surName']."<br>";
		echo "<b>Remarks :</b>". $result['remarks'] ."<br>";
	}
}
