<?php
	include('rose_purchaseOrderMakingSql.php');
	include('rose_purchaseOrderSendEmail.php');
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	function generateCodeNew($textValue,$prefix,$textLength)
	{
		$zeroCount = $textLength - strlen($textValue);
		$text = $prefix;
		while($zeroCount > 0)
		{
			$text .= "0";
			$zeroCount--;
		}
		$text .= $textValue;
		
		return $text;
	}
?>
<style type="text/css">
		td{font-family: Arial; font-size: 16pt;}
		#scaled-frame { width: 1400px; height: 100; border: 3px; }
		#scaled-frame2 { width: 1400px; height: 500; border: 0px; }
		#scaled-frame3 { width: 1400px; height: 200; border: 0px; }	
		#scaled-frame4 { width: 1400px; height: 200px; border: 0px; }	
		#scaled-frame5 { width: 250px; height: 500px; border: 0px; }	
	</style>
<?php	
	$Print = (isset($_POST['Print'])) ? $_POST['Print'] : "";
	$formCheck = (isset($_POST['formCheck'])) ? $_POST['formCheck'] : "";
	$sequence= isset($_POST['sequence']) ? $_POST['sequence']: 0;
	// echo "ROSE".$Print;	 echo "<br>count".count($formCheck);	 echo "<br>sequence".($sequence);
	
	if($sequence>0)
	{
		$updateKeyz= isset($_POST['updateKey']) ? $_POST['updateKey']: 0;
		$updatePONumz= isset($_POST['updatePONum']) ? $_POST['updatePONum']: 0;
		$emailValue= isset($_POST['email']) ? $_POST['email']: "";
		//echo "email: ".($emailValue);			 echo "UPDATE data:".($sequence-1);		
		 //echo " Key:".$updateKeyz;		echo " PO#:".$updatePONumz;
    	// echo "<br>Key:".$updateKeyz;		echo " PO#:".$updatePONumz;
		
		purchasingInsertOrders($updateKeyz,$updatePONumz,$emailValue);
		//purchasingEmailOrders($updatePONumz);
		purchasingInsertTMP($updatePONumz);
	}
	if($formCheck=="" or $sequence==count($formCheck))
	{
		?>		
		<form method="GET" action="gerald_purchaseOrderMakingSummary.php">
			<input type="submit" name="submit" value="BACK">
		</form>		
		<?php
	}
	else
	{
		?>		
		<table border=1>
		<tr>
            <td>
				<form method="GET" action="gerald_purchaseOrderMakingSummary.php">
				<input type="submit" name="submit" value="BACK">
				</form>
			</td>
            <td>
				<form method="POST" action="rose_printOut.php">
				
				Email this PO:<input type="checkbox" name="email" value="withemail" checked>
				<input type="submit" name="submit" value="FINISH PRINTING">
			</td>
		</tr>
		
        <tr>
            <td valign='top' colspan=2>
			<?php
			$updateKey="";
			$updatePONum="";
			for($x=0;$x<count($formCheck);$x++)
			{
				?>
				<input type="hidden" name="formCheck[]" value="<?php echo $formCheck[$x]; ?>">
				<?php
				if($x==$sequence)
				{
					//echo "<br>check".$formCheck[$x];
						$dataExplode=explode("`",$formCheck[$x]);
						$dataExplode2=explode("-",$dataExplode[1]);
					$supplierId=$dataExplode[0];			
					$supplierType=$dataExplode2[0];
					$currency=$dataExplode2[1];
					
					
					$supplierAlias = $shipment = $terms = '';
					$sql = "SELECT supplierAlias, shipment, terms FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
					if($supplierType==2)	$sql = "SELECT subconAlias, shipment, terms FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
					$querySupplier = $db->query($sql);
					if($querySupplier AND $querySupplier->num_rows > 0)
					{
						$resultSupplier = $querySupplier->fetch_row();
						$supplierAlias = $resultSupplier[0];
						$shipment = $resultSupplier[1];
						$shipmentType = $resultSupplier[1];
						$terms = $resultSupplier[2];
					}
					$latestPoNo = '';
					if($_GET['country']==2)
					{
						$supplierAliasLen = strlen($supplierAlias);
						$yNLen = strlen(date('yn'));
						
						$maxNumber = 0;
						//~ $sql = "SELECT  CAST(SUBSTRING(poNumber,".($supplierAliasLen+$yNLen)."+1) AS SIGNED) AS number FROM purchasing_podetailsnew WHERE poNumber LIKE '".$supplierAlias."%' AND poIssueDate >= '2017-10-01' ORDER BY `number` DESC LIMIT 1";
						$sql = "SELECT  CAST(SUBSTRING(poNumber,".($supplierAliasLen+$yNLen)."+1) AS SIGNED) AS number FROM purchasing_podetailsnew WHERE poNumber LIKE '".$supplierAlias."%' AND poIssueDate >= '".date('Y-m-01')."' ORDER BY `number` DESC LIMIT 1";
						$queryCheckMaxPo = $db->query($sql);
						if($queryCheckMaxPo AND $queryCheckMaxPo->num_rows > 0)
						{
							$resultCheckMaxPo = $queryCheckMaxPo->fetch_assoc();
							$maxNumber = $resultCheckMaxPo['number'];
						}
						$maxNumber++;
						$numberCount = (strlen($maxNumber) > 3) ? strlen($maxNumber) : 3;
						$latestPoNo = generateCodeNew($maxNumber,strtoupper($supplierAlias).date('yn'),$numberCount);
					}
					else
					{
						$sql = "SELECT CAST(poNumber as unsigned) as number FROM `purchasing_podetailsnew` ORDER BY `number` DESC LIMIT 1";
						$sql = "SELECT CAST(poNumber as unsigned) as number FROM `purchasing_podetailsnew` WHERE poNumber != '0010651' ORDER BY `number` DESC LIMIT 1";
						$queryCheckMaxPo = $db->query($sql);
						if($queryCheckMaxPo AND $queryCheckMaxPo->num_rows > 0)
						{
							$resultCheckMaxPo = $queryCheckMaxPo->fetch_assoc();
							$latestPoNo = generateCode(($resultCheckMaxPo['number']+1),'',7);
						}
					}
					$poNumber=$latestPoNo;
					$updateKey=$formCheck[$x];
					$updatePONum=$poNumber;
					//purchasingInsertOrders($formCheck[$x],$poNumber);
					
					if($_GET['country']==2)
					{
						?>
						<!--
						<iframe align="center" src="gerald_purchaseOrderConverterJapan.php?key=<?php echo $formCheck[$x]; ?>" frameborder="yes" scrolling="yes" name="myIframe5" id="scaled-frame2"> </iframe>
						
						<iframe align="center" src="rose_purchaseOrderConverterJapanPreview.php?key=<?php echo $formCheck[$x]; ?>" frameborder="yes" scrolling="yes" name="myIframe5" id="scaled-frame2"> </iframe>
						-->
						<iframe align="center" src="rose_purchaseOrderConverterJapan.php?key=<?php echo $formCheck[$x]; ?>&poNumber=<?php echo $poNumber; ?>&saveFile=1" frameborder="yes" scrolling="yes" name="myIframe5" id="scaled-frame2"> </iframe>
						
						<?php
					}
					else
					{
						?>
						<iframe align="center" src="gerald_purchaseOrderConverter.php?key=<?php echo $formCheck[$x]; ?>&poNumber=<?php echo $poNumber; ?>" frameborder="yes" scrolling="yes" name="myIframe5" id="scaled-frame2"> </iframe>
						<?php
					}
				}
			}
			?>
			</td>
		</tr>
		
        </table>
			<input type="hidden" name="sequence" value="<?php echo ($sequence+1); ?>">
			<input type="hidden" name="updateKey" value="<?php echo $updateKey; ?>">
			<input type="hidden" name="updatePONum" value="<?php echo $updatePONum; ?>">
		</form>
		<?php
	}
?>
